<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Media;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Model\FFprobeService;
use Oktolab\MediaBundle\Event\EncodedEpisodeEvent;
use Oktolab\MediaBundle\OktolabMediaEvent;

/**
 * checks original video and starts encoding according to configurated resolutions.
 * after encoding, checks availability
 */
class EncodeEpisodeJob extends BprsContainerAwareJob {

    private $em;
    private $logbook;
    private $added_finalize;
    private $oktolab_media;

    /**
     * {@inheritdoc}
     */
    public function perform() {
        $this->oktolab_media = $this->getContainer()->get('oktolab_media');
        $episode = $this->oktolab_media->getEpisode($this->args['uniqID']);
        $this->logbook = $this->getContainer()->get('bprs_logbook');

        $this->logbook->info(
            'oktolab_media.episode_start_encodevideo',
            [],
            $this->args['uniqID']
        );

        if ($episode) {
            $this->encodeEpisode($episode);
        } else { // no episode found
            $this->logbook->error(
                'oktolab_media.episode_encodenoepisode',
                [],
                $this->args['uniqID']
            );
        }

        $this->logbook->info(
            'oktolab_media.episode_end_encodevideo',
            [],
            $this->args['uniqID']
        );
    }

    private function encodeEpisode($episode) {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $episode->setIsActive(false);
        $this->oktolab_media->setEpisodeStatus(
            $episode->getUniqID(),
            Episode::STATE_IN_PROGRESS
        );
        if (!$episode->getVideo()) {
            $this->oktolab_media->setEpisodeStatus(
                $episode->getUniqID(),
                Episode::STATE_IN_PROGRESS_NO_VIDEO
            );
        } else {
            // Remove all old medias
            $media_helper = $this->getContainer()->get('oktolab_media_helper');
            $media_helper->deleteMedia($episode);

            // encode each resolution
            $resolutions = $this->getContainer()->getParameter('oktolab_media.resolutions');
            $this->added_finalize = false;
            foreach($resolutions as $format => $resolution) {
                if ($resolution['stereomode'] == $episode->getStereomode()) {
                    $this->logbook->info(
                        'oktolab_media.episode_start_encoding_resolution',
                        ["%format%" => $format],
                        $this->args['uniqID']
                    );
                    $cmd = false;
                    $encoding_option = $this->getContainer()->get('oktolab_media_ffprobe')->detectEncodingOptionForResolution($resolution, $episode);
                    switch ($encoding_option) {
                        case FFprobeService::ENCODING_OPTION_VIDEO_COPY_ALL:
                            // video and audio stream can be copied
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_copy_all',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );

                            $cmd = sprintf(
                                    '-movflags +faststart -c:v copy -c:a copy'
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_VIDEO_COPY_VIDEO:
                            // video copy, audio encode
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_copy_video',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-movflags +faststart -c:v copy -c:a %s -ar %s -b:a %s -strict -2',
                                    $resolution['audio_codec'],
                                    $resolution['audio_sample_rate'],
                                    $resolution['audio_bitrate']
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_VIDEO_COPY_AUDIO:
                            // video encode, audio copy
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_copy_audio',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -c:a copy -preset %s',
                                    $resolution['crf_rate'],
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $resolution['video_codec'],
                                    $resolution['video_framerate'],
                                    $resolution['preset']
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_VIDEO_ENCODE_BOTH:
                            // video and audio must be encoded
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_encode_both',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -c:a %s -ar %s -b:a %s -strict -2 -preset %s',
                                    $resolution['crf_rate'],
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $resolution['video_codec'],
                                    $resolution['video_framerate'],
                                    $resolution['audio_codec'],
                                    $resolution['audio_sample_rate'],
                                    $resolution['audio_bitrate'],
                                    $resolution['preset']
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_VIDEO_ONLY_COPY:
                            // the file has no audio, but the video stream can be copied.
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_only_copy',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-movflags +faststart -c:v copy -an'
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_VIDEO_ONLY_ENCODE:
                            // the file has no audio, but the video stream can be encoded
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_video_only_encode',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -preset %s',
                                    $resolution['crf_rate'],
                                    $resolution['video_width'],
                                    $resolution['video_height'],
                                    $resolution['video_codec'],
                                    $resolution['video_framerate'],
                                    $resolution['preset']
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_AUDIO_COPY:
                            // the file is audio only and the stream can be copied
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_audio_copy',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-c:a copy'
                                );
                            break;

                        case FFprobeService::ENCODING_OPTION_AUDIO_CONVERT:
                            // the file is audio only and the stream can be encoded
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_audio_encode',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            $cmd = sprintf(
                                    '-c:a %s -r %s -b:a %s -preset %s',
                                    $resolution['crf_rate'],
                                    $resolution['audio_codec'],
                                    $resolution['audio_sample_rate'],
                                    $resolution['audio_bitrate'],
                                    $resolution['preset']
                                );
                            break;

                        default:
                            $this->logbook->info(
                                'oktolab_media.encodeEpisodeJob_unknown_encoding',
                                ["%format%" => $format],
                                $this->args['uniqID']
                            );
                            break;
                    }
                    if ($cmd) {
                        $this->executeFFmpegForMedia(
                            $cmd,
                            $format,
                            $resolution,
                            $this->prepareNewMedia($episode, $format, $resolution)
                        );
                    }
                } //endif stereomode
            } // foreach resolution

            // delete original file after encoding processes
            $this->deleteOriginalIfConfigured();

            // add sprite generation job
            $this->oktolab_media->addGenerateThumbnailSpriteJob($episode->getUniqID());

            // add finalize episode job, set new episode status
            $this->finalizeEpisode($episode);

        } // if episode has video
    }

    /**
     * creates a media object and runs the ffmpeg command for the given resolution.
     * the command will be tracked to update the progress from 0 - 100 in realtime.
     * will move the asset from the cache adapter to the adapter in the resolution if defined.
     * triggers the finalize episode function once for the whole job
     */
    private function executeFFmpegForMedia($cmd, $format, $resolution, $media) {
        $ffmpeg_start = microtime(true);
        $this->getContainer()->get('oktolab_media_ffmpeg')
            ->executeFFmpegForMedia(
                $resolution['encoder'],
                $cmd,
                $media
            )
        ;
        $ffmpeg_stop = microtime(true);

        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_FINISHED);
        $media->setPublic($resolution['public']);
        $this->em->persist($media);
        $this->em->flush();

        $this->logbook->info(
            'oktolab_media.episode_end_saving_media',
            [
                '%format%' => $format,
                '%seconds%' => round($ffmpeg_stop - $ffmpeg_start)
            ],
            $this->args['uniqID']
        );

        // move encoded media from "cache" to config adapter or adapter of the original file
        $this->getContainer()->get('bprs.asset')->moveAsset(
            $media->getAsset(),
            $resolution['adapter'] ? $resolution['adapter'] : $media->getEpisode()->getVideo()->getAdapter()
        );

        // add finalize episode job, set new episode status
        if (!$this->added_finalize) {
            $this->finalizeEpisode($media->getEpisode());
            $this->added_finalize = true;
        }
    }

    private function prepareNewMedia($episode, $format, $resolution)
    {
        $media = new Media();
        $episode->addMedia($media);
        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_INPROGRESS);
        $media->setPublic(false);
        $media->setQuality($format);
        $media->setSortNumber($resolution['sortNumber']);
        $media->setAsset(
            $this->createNewCacheAssetForResolution($episode, $resolution)
        );

        $this->em->persist($media);
        $this->em->persist($episode);
        $this->em->flush();

        return $media;
    }

    /**
     * adds finalize job
     * and dispatches encoded_episode event
     */
    private function finalizeEpisode($episode) {
        $event = new EncodedEpisodeEvent($episode);
        $this->getContainer()->get('event_dispatcher')->dispatch(
            OktolabMediaEvent::ENCODED_EPISODE,
            $event
        );

        $this->oktolab_media->addFinalizeEpisodeJob(
            $episode->getUniqID(),
            false,
            true
        );
    }

    /**
     * returns an empty asset to be used in an encoding situation.
     * the filekey, mimetype and adapter are already set for ffmpeg
     */
    private function createNewCacheAssetForResolution($episode, $resolution) {
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
        $asset->setMimetype($resolution['mimetype']);

        return $asset;
    }

    /**
     * if the configuration flag keep_original is set to false and encoding
     * of all media was successful (100%), the uploaded original video
     * will be replaced with the media with the highest sortnumber
     * directly after encoding
     */
    private function deleteOriginalIfConfigured() {
        $episode = $this->oktolab_media->getEpisode($this->args['uniqID']);
        if (!$this->getContainer()->getParameter('oktolab_media.keep_original')) {
            $this->logbook->info(
                'oktolab_media.episode_encode_remove_old_media',
                [],
                $this->args['uniqID']
            );
            if (count($episode->getMedia())) {
                // check if any media is incomplete.
                $all_media_ok = true;
                foreach ($episode->getMedia() as $media) {
                    if ($media->getProgress() < 100) {
                        $all_media_ok = false;
                    }
                }
                if ($all_media_ok) { // if all media ok, replace original file
                    $this->getContainer()->get('oktolab_media_helper')->deleteVideo($episode);
                    $best_media = $episode->getMedia()[0];
                    foreach ($episode->getMedia() as $media) {
                        if ($media->getSortNumber() > $best_media->getSortNumber()) {
                            $best_media = $media;
                        }
                    }
                    $episode->setVideo($best_media->getAsset());
                } else { // some media were not okay. keep original for further encoding.
                    $episode->setTechnicalStatus(Episode::STATE_PROGRESS_FAILED);
                    $this->logbook->info(
                        'oktolab_media.episode_encode_media_failed',
                        [],
                        $this->args['uniqID']
                    );
                }
                $this->em->persist($episode);
                $this->em->flush();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'Encode Video';
    }
}
