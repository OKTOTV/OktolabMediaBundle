<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Media;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Event\EncodedEpisodeEvent;
use Oktolab\MediaBundle\OktolabMediaEvent;
//TODO: flexibility to encode audio only too!

/**
 * checks original video and starts encoding according to configurated resolutions.
 * after encoding, checks availability
 */
class EncodeVideoJob extends BprsContainerAwareJob
{
    private $em;
    private $logbook;

    public function perform() {
        $episode = $this->getContainer()->get('oktolab_media')->getEpisode($this->args['uniqID']);
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info('oktolab_media.episode_start_encodevideo', [], $this->args['uniqID']);

        if ($episode) {
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $episode->setIsActive(false);
            $this->getContainer()->get('oktolab_media')->setEpisodeStatus($episode->getUniqID(), Episode::STATE_IN_PROGRESS);
            if (!$episode->getVideo()) {
                $this->getContainer()->get('oktolab_media')->setEpisodeStatus($episode->getUniqID(), Episode::STATE_IN_PROGRESS_NO_VIDEO);
            } else {
                // Remove all old medias
                $this->purgeEpisodeMedias($episode);

                // get Medatinfo of episode video
                $metainfo = $this->getStreamInformationsOfEpisode($episode);
                $episode->setDuration($metainfo['video']['duration']);
                $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());
                // encode each resolution
                $resolutions = $this->getContainer()->getParameter('oktolab_media.resolutions');
                foreach($resolutions as $format => $resolution) {
                    // create new asset in "cache"
                    $this->logbook->info('oktolab_media.episode_start_encoding_resolution', [], $this->args['uniqID']);
                    $cmd = "";
                    if ($this->resolutionIsTheSame($resolution, $metainfo['video'])) { //resolution is the same
                        $media = $this->createMediaForEpisode($episode, $format, $resolution);
                        $path = $this->getContainer()->get('bprs.asset_helper')->getPath($media->getAsset(), true);
                        if ($this->videoCanBeCopied($resolution, $metainfo['video'])) { //videocodec is the same, can be copied
                            if ($this->audioCanBeCopied($resolution, $metainfo['audio'])) { // audiocodec is the same, can be copied
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a copy "%s" 2>&1',
                                        $uri,
                                        $path
                                    );
                            } else { // just copy video
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a aac -strict -2 "%s" 2>&1',
                                        $uri,
                                        $path
                                    );
                            }
                        } else { // video can not be copied (encode me)
                            $cmd = sprintf(
                                    'ffmpeg -i "%s" -deinterlace -crf %s -movflags +faststart -c:v h264 -r 50 -c:a aac -strict -2 -preset %s "%s" 2>&1',
                                    $uri,
                                    $resolution['crf_rate'],
                                    $resolution['preset'],
                                    $path
                                );
                        }
                        $this->executeFFmpegForMedia($cmd, $media);
                        $this->saveMedia($media, $resolution);
                    } elseif ($this->resolutionCanBeEncoded($resolution, $metainfo['video'])) { // resolution can be encoded
                        $media = $this->createMediaForEpisode($episode, $format, $resolution);
                        $path = $this->getContainer()->get('bprs.asset_helper')->getPath($media->getAsset(), true);
                        if ($this->audioCanBeCopied($resolution, $metainfo['audio'])) { // audiocodec is the same, can be copied
                            $cmd = sprintf(
                                    'ffmpeg -i "%s" -deinterlace -crf %s -s %sx%s -movflags +faststart -c:v h264 -r 50 -c:a copy -preset %s "%s" 2>&1',
                                    $uri,
                                    $resolution['crf_rate'],
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $resolution['preset'],
                                    $path
                                );
                        } else { // encode video and audio in resolution
                            $cmd = sprintf(
                                    'ffmpeg -i "%s" -deinterlace -crf %s -s %sx%s -movflags +faststart -c:v copy -c:a aac -strict -2 -preset %s "%s" 2>&1',
                                    $uri,
                                    $resolution['crf_rate'],
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $resolution['preset'],
                                    $path
                                );
                        }
                        $this->executeFFmpegForMedia($cmd, $media);
                        $this->saveMedia($media, $resolution);
                    } else { // the resolution can not be encoded. source does not fit the resolutions expectations
                        $this->logbook->info('oktolab_media.episode_cannot_encoderesolution', ['%format%'=> $format], $this->args['uniqID']);
                    }
                }
                $this->deleteOriginalIfConfigured();
                $this->finalizeEpisode($episode);
                $this->logbook->info('oktolab_media.episode_end_encodevideo', [], $this->args['uniqID']);
            }
        } else {
            $this->logbook->error('oktolab_media.episode_encode_error', [], $this->args['uniqID']);
        }
    }

    public function getName() {
        return 'Encode Video';
    }

    private function executeFFmpegForMedia($cmd, $media) {
        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_INPROGRESS);
        $this->em->persist($media);
        $this->em->flush();

        $durationInSeconds = $media->getEpisode()->getDuration();
        $currentInSeconds = 0;
        $currentProgress = 0;

        // open (ffmpeg) process, read stdout
        $fp = popen($cmd, "r");
        while(!feof($fp)) {
            // read outputstream of ffmpeg
            $chunk = fread($fp, 1024);

            // try to get the duration information at the beginning of the ffmpeg output.
            if (!$durationInSeconds) {
                preg_match("/Duration: (.*?), start:/", $chunk, $matches);
                if (array_key_exists(1, $matches)) {
                    list($hours,$minutes,$seconds) = explode(":",$matches[1]);
                    // calculate the duration in seconds. Used to calculate overall progress in percent.
                    $durationInSeconds = (($hours * 3600) + ($minutes * 60) + $seconds);
                }
            }

            // try to get the current encoding information of ffmpeg
            preg_match("/time=(.*?) bitrate/", $chunk, $progress);
            if (array_key_exists(1, $progress)) {
                list($hours,$minutes,$seconds) = explode(":",$progress[1]);
                $seconds = (($hours * 3600) + ($minutes * 60) + $seconds);
                // calculate current second ffmpeg is at.
                $currentInSeconds = round($seconds);

                // calculate percentage using the duration in seconds and current second
                if ($durationInSeconds) {
                    $percent = round((($currentInSeconds * 100)/($durationInSeconds)));
                    if ($percent > $currentProgress) {
                        $currentProgress = $percent;
                        // update information of the media. happens every percentage update
                        $media->setProgress($currentProgress);
                        $this->em->persist($media);
                        $this->em->flush();
                    }
                }
            }
            // flush the content to the browser
            flush();
        }
        fclose($fp);
        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_FINISHED);
    }

    private function resolutionIsTheSame($resolution, $metainfo)
    {
        return
            $resolution['video_width'] == $metainfo['width'] &&
            $resolution['video_height'] == $metainfo['height']
        ;
    }

    /**
     * determines if the stream can simply be copied.
     * important factors are codec, framerate and a maximum bitrate.
     */
    private function videoCanBeCopied($resolution, $metainfo)
    {
        return
            $resolution['video_codec'] == $metainfo['codec_name'] &&
            $resolution['video_framerate'] == $metainfo['avg_frame_rate'] &&
            $metainfo['bit_rate'] <= $resolution['video_bitrate']
        ;
    }

    private function audioCanBeCopied($resolution, $metainfo)
    {
        return
            $resolution['audio_codec'] == $metainfo['codec_name'] &&
            $resolution['audio_sample_rate'] >= $metainfo['sample_rate']
        ;
    }

    /**
     * determines if the resolution is the minimum size required to be encoded
     */
    private function resolutionCanBeEncoded($resolution, $metainfo)
    {
        return
            $resolution['video_width'] <= $metainfo['width'] &&
            $resolution['video_height'] <= $metainfo['height']
        ;
    }

    /**
     * create media for episode and persist it in the database before
     * the encoding process starts.
     */
    private function createMediaForEpisode($episode, $format, $resolution)
    {
        $this->logbook->info(
            'oktolab_media.episode_start_saving_media',
            [],
            $this->args['uniqID'
        ]);
        $media = new Media();
        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_TOPROGRESS);
        $media->setQuality($format);
        $media->setSortNumber($resolution['sortNumber']);
        $media->setPublic($resolution['public']);
        $media->setAsset(
            $this->createNewCacheAssetForResolution($episode, $resolution)
        );

        $episode->addMedia($media);
        $this->em->persist($media);
        $this->em->persist($episode);
        $this->em->flush();
        return $media;
    }

    /**
     * persists media in the database and adds a move asset job to move
     * the asset from cache to the video filesystem
     */
    private function saveMedia($media, $resolution)
    {
        $this->em->persist($media);
        $this->em->flush();

        $this->logbook->info(
            'oktolab_media.episode_end_saving_media',
            [],
            $this->args['uniqID']
        );

        // move encoded media from "cache" to adapter of the original file
        $this->getContainer()->get('bprs.asset_job')->addMoveAssetJob(
            $media->getAsset(),
            $resolution['adapter'] ? $resolution['adapter'] : $media->getEpisode()->getVideo()->getAdapter()
        );
    }

    private function finalizeEpisode($episode)
    {
        $event = new EncodedEpisodeEvent($episode);
        $this->getContainer()->get('event_dispatcher')->dispatch(
            OktolabMediaEvent::ENCODED_EPISODE,
            $event
        );

        $this->getContainer()->get('oktolab_media')->setEpisodeStatus(
            $episode->getUniqID(),
            Episode::STATE_IN_FINALIZE_QUEUE
        );

        $this->getContainer()->get('bprs_jobservice')->addJob(
        'Oktolab\MediaBundle\Model\FinalizeVideoJob',
            [
                'uniqID'=> $episode->getUniqID()
            ]
        );
    }

    private function purgeEpisodeMedias($episode)
    {
        $media_helper = $this->getContainer()->get('oktolab_media_helper');
        $media_helper->deleteMedia($episode);
    }

    /**
     * extracts streaminformations of an episode from its video.
     * returns false if informations can't be extracted
     * returns array of video and audio info.
     */
    private function getStreamInformationsOfEpisode($episode)
    {
        $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());
        if (!$uri) { // can't create uri of episode video
            $this->logbook->error('oktolab_media.episode_encode_no_streams', [], $this->args['uniqID']);
            return false;
        }

        $metainfo = json_decode(shell_exec(sprintf('ffprobe -v error -show_streams -print_format json %s', $uri)), true);
        $metadata = ['video' => false, 'audio' => false];

        foreach ($metainfo['streams'] as $stream) {
            if ($metadata['video'] && $metadata['audio']) {
                break;
            }
            if ($stream['codec_type'] == "audio") {
                $metadata['audio'] = $stream;
            }
            if ($stream['codec_type'] == "video") {
                $metadata['video'] = $stream;
            }
        }
        return $metadata;
    }

    public function createNewCacheAssetForResolution($episode, $resolution)
    {
        $asset = $this->getContainer()->get('bprs.asset')->createAsset();
        $asset->setFilekey(
            sprintf('%s.%s',$asset->getFilekey(), $resolution['container'])
        );
        $asset->setAdapter(
            $this->getContainer()->getParameter(
                'oktolab_media.encoding_filesystem'
            )
        );
        $asset->setName($episode->getVideo()->getName());
        $asset->setMimetype('video/quicktime');

        return $asset;
    }

    private function deleteOriginalIfConfigured()
    {
        $episode = $this->getContainer()->get('oktolab_media')->getEpisode($this->args['uniqID']);
        if (!$this->getContainer()->getParameter('oktolab_media.keep_original')) {
            $this->logbook->info('oktolab_media.episode_encode_remove_old_media', [], $this->args['uniqID']);
            if (count($episode->getMedia())) {
                $this->getContainer()->get('oktolab_media_helper')->deleteVideo($episode);
                $best_media = $episode->getMedia()[0];
                foreach ($episode->getMedia() as $media) {
                    if ($media->getSortNumber() > $best_media->getSortNumber()) {
                        $best_media = $media;
                    }
                }
                $episode->setVideo($best_media->getAsset());

                $this->em->persist($episode);
                $this->em->flush();
            }
        }
    }
}
