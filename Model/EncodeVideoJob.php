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

            $this->getContainer()->get('oktolab_media')->setEpisodeStatus($episode->getUniqID(), Episode::STATE_IN_PROGRESS);
            if (!$episode->getVideo()) {
                $this->getContainer()->get('oktolab_media')->setEpisodeStatus($episode->getUniqID(), Episode::STATE_IN_PROGRESS_NO_VIDEO);
            } else {
                // Remove all old medias
                $this->purgeEpisodeMedias($episode);

                // get Medatinfo of episode video
                $metainfo = $this->getStreamInformationsOfEpisode($episode);
                $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());
                // encode each resolution
                $resolutions = $this->getContainer()->getParameter('oktolab_media.resolutions');
                foreach($resolutions as $format => $resolution) {
                    // create new asset in "cache"
                    $asset = $this->createNewCacheAssetForResolution($episode, $resolution);
                    $path = $this->getContainer()->get('bprs.asset_helper')->getPath($asset, true);

                    if ($this->resolutionIsTheSame($resolution, $metainfo['video'])) { //resolution is the same
                        if ($this->videoCanBeCopied($resolution, $metainfo['video'])) { //videocodec is the same, can be copied
                            if ($this->audioCanBeCopied($resolution, $metainfo['audio'])) { // audiocodec is the same, can be copied
                                shell_exec(sprintf('ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a copy "%s"', $uri, $path));
                            } else { // just copy video
                                shell_exec(sprintf('ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a aac -strict -2 "%s"', $uri, $path));
                            }
                        } else { // video can not be copied (encode me)
                            shell_exec(sprintf('ffmpeg -i "%s" -deinterlace -crf 21 -movflags +faststart -c:v h264 -r 50 -c:a aac -strict -2 "%s"', $uri, $path));
                        }
                        $this->saveMedia($format, $resolution, $asset, $episode);
                    } elseif ($this->resolutionCanBeEncoded($resolution, $metainfo['video'])) { // resolution can be encoded
                        if ($this->audioCanBeCopied($resolution, $metainfo['audio'])) { // audiocodec is the same, can be copied
                            shell_exec(
                                sprintf(
                                    'ffmpeg -i "%s" -deinterlace -crf 21 -s %sx%s -movflags +faststart -c:v h264 -r 50 -c:a copy "%s"',
                                    $uri,
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $path
                                )
                            );
                        } else { // encode video and audio in resolution
                            shell_exec(
                                sprintf(
                                    'ffmpeg -i "%s" -deinterlace -crf 21 -s %sx%s -movflags +faststart -c:v copy -c:a aac -strict -2 "%s"',
                                    $uri,
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $path
                                )
                            );
                        }
                        $this->saveMedia($format, $resolution, $asset, $episode);
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

    private function resolutionIsTheSame($resolution, $metainfo)
    {
        return $resolution['video_width'] == $metainfo['width'] && $resolution['video_height'] == $metainfo['height'];
    }

    private function videoCanBeCopied($resolution, $metainfo)
    {
        return $resolution['video_codec'] == $metainfo['codec_name'] && $resolution['video_framerate'] == $metainfo['avg_frame_rate'];
    }

    private function audioCanBeCopied($resolution, $metainfo)
    {
        return $resolution['audio_codec'] == $metainfo['codec_name'] && $resolution['audio_sample_rate'] >= $metainfo['sample_rate'];
    }

    private function resolutionCanBeEncoded($resolution, $metainfo)
    {
        return $resolution['video_width'] <= $metainfo['width'] && $resolution['video_height'] <= $metainfo['height'];
    }

    private function saveMedia($format, $resolution, $asset, $episode)
    {
        $media = new Media();
        $media->setQuality($format);
        $media->setSortNumber($resolution['sortNumber']);
        $media->setAsset($asset);
        $media->setPublic($resolution['public']);
        $episode->addMedia($media);
        $this->em->persist($asset);
        $this->em->persist($media);
        $this->em->persist($episode);
        $this->em->flush();

        // move encoded media from "cache" to adapter of the original file
        $this->getContainer()->get('bprs_jobservice')->addJob(
        'Bprs\AssetBundle\Model\MoveAssetJob',
            [
                'filekey'=> $asset->getFilekey(),
                'adapter' => $resolution['adapter'] ? $resolution['adapter'] : $episode->getVideo()->getAdapter()
            ]
        );
    }

    private function finalizeEpisode($episode)
    {
        $event = new EncodedEpisodeEvent($episode);
        $this->getContainer()->get('event_dispatcher')->dispatch(OktolabMediaEvent::ENCODED_EPISODE, $event);
        $this->getContainer()->get('oktolab_media')->setEpisodeStatus($episode->getUniqID(), Episode::STATE_IN_FINALIZE_QUEUE);
        $this->getContainer()->get('bprs_jobservice')->addJob(
        'Oktolab\MediaBundle\Model\FinalizeVideoJob',
            [
                'uniqID'=> $episode->getUniqID()
            ]
        );
    }

    private function purgeEpisodeMedias($episode)
    {
        foreach($episode->getMedia() as $media) {
            $this->em->remove($media);
            $episode->removeMedia($media);
            $this->em->flush();
            if ($media->getAsset() && $media->getAsset() != $episode->getVideo()) {
                $this->getContainer()->get('bprs.asset_helper')->deleteAsset($media->getAsset());
            }
        }
        $this->em->flush();
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
        $class = $this->getContainer()->getParameter('oktolab_media.asset_class');
        $asset = new $class;
        $key = sprintf('%s.%s', uniqId(), $resolution['container']);
        $asset->setFilekey($key);
        $asset->setAdapter($this->getContainer()->getParameter('oktolab_media.encoding_filesystem'));
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
                $best_media = $episode->getMedia()[0];
                foreach ($episode->getMedia() as $media) {
                    if ($media->getSortNumber() > $best_media->getSortNumber()) {
                        $best_media = $media;
                    }
                }
                $origin = $episode->getVideo();
                $episode->setVideo($best_media->getAsset());
                $this->getContainer()->get('bprs.asset')->deleteAsset($origin);

                $this->em->persist($episode);
                $this->em->flush();
            }
        }
    }
}
