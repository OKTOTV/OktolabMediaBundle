<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Media;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Event\EncodedEpisodeEvent;
use Oktolab\MediaBundle\OktolabMediaEvent;

/**
 * checks original video and starts encoding according to configurated resolutions.
 * after encoding, checks availability
 */
class EncodeEpisodeJob extends BprsContainerAwareJob {
    // VIDEO FILES
    const ENCODING_OPTION_VIDEO_COPY_ALL   = 100; // the video and auido stream can be copied for this resolution
    const ENCODING_OPTION_VIDEO_COPY_VIDEO = 90;  // the video stream can be copied, audio must be converted
    const ENCODING_OPTION_VIDEO_COPY_AUDIO = 80;  // the video must be converted, audio can be copied
    const ENCODING_OPTION_VIDEO_ENCODE_BOTH = 70; // audio and video must be converted

    const ENCODING_OPTION_VIDEO_ONLY_COPY  = 60;  // video file without audio. but the video can be copied
    const ENCODING_OPTION_VIDEO_ONLY_ENCODE = 50; // video file without audio. the video stream must be encoded for this resolution

    // AUDIO FILES
    const ENCODING_OPTION_AUDIO_COPY       = 40;  // audio file! stream can be copied
    const ENCODING_OPTION_AUDIO_CONVERT    = 30;  // audio file! stream must be encoded

    const ENCODING_OPTION_NONE             = 0;   // no reliable information in file found

    // Media types
    const MEDIA_TYPE_VIDEO                  = 20; // mediatype seems to be a videofile
    const MEDIA_TYPE_VIDEO_ONLY             = 15; // mediatype seems to be a videofile without sound
    const MEDIA_TYPE_AUDIO                  = 10; // mediatype seems to be a soundfile
    const MEDIA_TYPE_UNKNOWN                = 0; // unknown mediatype (or not yet supported)

    private $em;
    private $logbook;
    private $added_finalize;

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
                $media_helper = $this->getContainer()->get('oktolab_media_helper');
                $media_helper->deleteMedia($episode);

                // get Medatinfo of episode video
                $metainfo = $this->getStreamInformationsOfEpisode($episode);
                $episode->setDuration($metainfo['video']['duration']);
                $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());

                // encode each resolution
                $resolutions = $this->getContainer()->getParameter('oktolab_media.resolutions');
                $this->added_finalize = false;
                foreach($resolutions as $format => $resolution) {
                    // create new asset in "cache"
                    if ($resolution['stereomode'] == $episode->getStereomode()) {
                        $this->logbook->info('oktolab_media.episode_start_encoding_resolution', ["%format%" => $format], $this->args['uniqID']);
                        $cmd = false;
                        $encoding_option = $this->detectEncodingOptionForResolution($resolution, $metainfo);
                        switch ($encoding_option) {
                            case $this::ENCODING_OPTION_VIDEO_COPY_ALL:
                                // video and audio stream can be copied
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_copy_all',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );

                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a copy',
                                        $uri
                                    );
                                break;

                            case $this::ENCODING_OPTION_VIDEO_COPY_VIDEO:
                                // video copy, audio encode
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_copy_video',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a %s -ar %s -strict -2',
                                        $uri,
                                        $resolution['audio_codec'],
                                        $resolution['audio_sample_rate']
                                    );
                                break;

                            case $this::ENCODING_OPTION_VIDEO_COPY_AUDIO:
                                // video encode, audio copy
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_copy_audio',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -c:a copy -preset %s',
                                        $uri,
                                        $resolution['crf_rate'],
                                        $resolution['video_width'],
                                        $resolution['video_height'],
                                        $resolution['video_codec'],
                                        $resolution['video_framerate'],
                                        $resolution['preset']
                                    );
                                break;

                            case $this::ENCODING_OPTION_VIDEO_ENCODE_BOTH:
                                // video and audio must be encoded
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_encode_both',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -c:a %s -ar %s -strict -2 -preset %s',
                                        $uri,
                                        $resolution['crf_rate'],
                                        $resolution['video_width'],
                                        $resolution['video_height'],
                                        $resolution['video_codec'],
                                        $resolution['video_framerate'],
                                        $resolution['audio_codec'],
                                        $resolution['audio_sample_rate'],
                                        $resolution['preset']
                                    );
                                break;

                            case $this::ENCODING_OPTION_VIDEO_ONLY_COPY:
                                // the file has no audio, but the video stream can be copied.
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_only_copy',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -movflags +faststart -c:v copy -an',
                                        $uri
                                    );
                                break;

                            case $this::ENCODING_OPTION_VIDEO_ONLY_ENCODE:
                                // the file has no audio, but the video stream can be encoded
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_video_only_encode',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -deinterlace -crf %s -s %sx%s -movflags +faststart -c:v %s -r %s -preset %s',
                                        $uri,
                                        $resolution['crf_rate'],
                                        $resolution['video_width'],
                                        $resolution['video_height'],
                                        $resolution['video_codec'],
                                        $resolution['video_framerate'],
                                        $resolution['preset']
                                    );
                                break;

                            case $this::ENCODING_OPTION_AUDIO_COPY:
                                // the file is audio only, but the stream can be copied
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_audio_copy',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -c:a copy',
                                        $uri
                                    );
                                break;

                            case $this::ENCODING_OPTION_AUDIO_CONVERT:
                                // the file is audio only, but the stream can be encoded
                                $this->logbook->info(
                                    'oktolab_media.encodeEpisodeJob_audio_encode',
                                    ["%format%" => $format],
                                    $this->args['uniqID']
                                );
                                $cmd = sprintf(
                                        'ffmpeg -i "%s" -c:a %s -r %s -b:a %s -preset %s',
                                        $uri,
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
                            $this->executeFFmpegForMedia($cmd, $format, $resolution, $episode);
                        }
                    } //endif stereomode
                } // foreach resolution

                // delete original file after encoding processes
                $this->deleteOriginalIfConfigured();

                // add sprite generation job
                $this->getContainer()->get('oktolab_media')->addGenerateThumbnailSpriteJob($episode->getUniqID());

                // add finalize episode job, set new episode status
                $this->finalizeEpisode($episode);

            } // if episode has video

            $this->logbook->info(
                'oktolab_media.episode_end_encodevideo',
                [],
                $this->args['uniqID']
            );

        } else { // no episode found
            $this->logbook->error(
                'oktolab_media.episode_encodenoepisode',
                [],
                $this->args['uniqID']
            );
        }
    }

    private function executeFFmpegForMedia($cmd, $format, $resolution, $episode) {
        $this->logbook->info(
            'oktolab_media.episode_start_saving_media',
            [
                '%cmd%'    => $cmd,
                '%format%' => $format
            ],
            $this->args['uniqID']
        );

        $media = new Media();
        $media->setStatus(Media::OKTOLAB_MEDIA_STATUS_MEDIA_INPROGRESS);
        $media->setQuality($format);
        $media->setSortNumber($resolution['sortNumber']);
        $media->setPublic(false);
        $media->setAsset(
            $this->createNewCacheAssetForResolution($episode, $resolution)
        );

        $episode->addMedia($media);
        $this->em->persist($media);
        $this->em->persist($episode);
        $this->em->flush();

        $durationInSeconds = $media->getEpisode()->getDuration();
        $currentInSeconds = 0;
        $currentProgress = 0;
        $ffmpeg_start = microtime(true);

        $path = $this->getContainer()->get('bprs.asset_helper')->getPath($media->getAsset(), true);
        $cmd = sprintf('%s "%s" 2>&1', $cmd, $path);

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
            $this->finalizeEpisode($episode);
            $this->added_finalize = true;
        }
    }

    /**
     * returns the possible encoding option to use the correct ffmpeg command
     */
    public function detectEncodingOptionForResolution($resolution, $metainfo) {
        switch ($this->getMediaType($metainfo)) {
            case $this::MEDIA_TYPE_AUDIO:
                $can_copy_audio = $this->audioCanBeCopied($resolution, $metainfo['audio']);
                $can_encode_audio = $this->audioCanBeEncoded($resolution, $metainfo['audio']);
                if ($can_copy_audio) {
                    return $this::ENCODING_OPTION_AUDIO_COPY;
                }

                if ($can_encode_audio) {
                    return $this::ENCODING_OPTION_AUDIO_CONVERT;
                }

                return $this::ENCODING_OPTION_NONE;
                break;

            case $this::MEDIA_TYPE_VIDEO:
                $can_copy_audio = $this->audioCanBeCopied($resolution, $metainfo['audio']);
                $can_encode_audio = $this->audioCanBeEncoded($resolution, $metainfo['audio']);
                $can_copy_video = $this->videoCanBeCopied($resolution, $metainfo['video']);
                $can_encode_video = $this->videoCanBeEncoded($resolution, $metainfo['video']);

                if ($can_copy_audio && $can_copy_video) {
                    return $this::ENCODING_OPTION_VIDEO_COPY_ALL;
                }
                if($can_copy_audio && $can_encode_video) {
                    return $this::ENCODING_OPTION_VIDEO_COPY_AUDIO;
                }
                if($can_encode_audio && $can_copy_video) {
                    return $this::ENCODING_OPTION_VIDEO_COPY_VIDEO;
                }
                if($can_encode_audio && $can_encode_video) {
                    return $this::ENCODING_OPTION_VIDEO_ENCODE_BOTH;
                }
                return $this::ENCODING_OPTION_NONE;
                break;

            case $this::MEDIA_TYPE_VIDEO_ONLY:
                $can_copy_video = $this->videoCanBeCopied($resolution, $metainfo['video']);
                $can_encode_video = $this->videoCanBeEncoded($resolution, $metainfo['video']);

                if ($can_copy_video) {
                    return $this::ENCODING_OPTION_VIDEO_ONLY_COPY;
                } elseif ($can_encode_video) {
                    return $this::ENCODING_OPTION_VIDEO_ONLY_ENCODE;
                }
                return $this::ENCODING_OPTION_NONE;
                break;
            default:
                # media type is unknown!
                return $this::ENCODING_OPTION_NONE;
                break;
        }
    }

    /**
     * determines if the stream can simply be copied. (videostreams)
     * important factors are codec, framerate and a maximum bitrate.
     */
    private function videoCanBeCopied($resolution, $metainfo) {
        return
            // codec is the same (for example h264)
            $resolution['video_codec'] == $metainfo['codec_name'] &&
            // framerate is the same (for example 50)
            $resolution['video_framerate'] == $metainfo['avg_frame_rate'] &&
            // resolution is the same
            $resolution['video_width'] == $metainfo['width'] &&

            $resolution['video_height'] == $metainfo['height'] &&
            // average bitrate is lower or same as in resolution
            $resolution['video_bitrate'] >= $metainfo['bit_rate']
        ;
    }

    /**
     * determines if the resolution is the minimum size required to be encoded
     */
    private function videoCanBeEncoded($resolution, $metainfo) {
        return
            $resolution['video_width'] <= $metainfo['width'] &&
            $resolution['video_height'] <= $metainfo['height']
        ;
    }

    /**
     * determines if the stream can be copied (audiostreams)
     * important factors are codec, sample_rate and bitrate
     */
    private function audioCanBeCopied($resolution, $metainfo) {
        return
            $resolution['audio_codec'] == $metainfo['codec_name'] &&
            $resolution['audio_sample_rate'] == $metainfo['sample_rate'] &&
            $resolution['audio_bitrate'] >= $metainfo['bit_rate']
        ;
    }

    /**
     * determines if the the audiotrack meets minimum standards to be encoded
     */
    private function audioCanBeEncoded($resolution, $metainfo) {
        return true;
        return
            $resolution['audio_sample_rate'] >= $metainfo['sample_rate'] &&
            $resolution['audio_bitrate'] <= $metainfo['bit_rate']
        ;
    }

    /**
     * returns the mediatype depending on given metainfos
     * MEDIA_TYPE_AUDIO if soundfile,
     * MEDIA_TYPE_VIDEO if videofile,
     * MEDIA_TYPE_VIDEO_ONLY if videofile without soundstream.
     */
    public function getMediaType($metainfo) {
        if (
            $metainfo['audio'] != false &&
            (
                $metainfo['video'] == false ||
                $metainfo['video']['codec_name'] == 'png' ||
                $metainfo['video']['codec_name'] == 'jpg'
            )
        ) {
            return $this::MEDIA_TYPE_AUDIO;
        }

        if ($metainfo['video'] != false && $metainfo['audio'] == false) {
            return $this::MEDIA_TYPE_VIDEO_ONLY;
        }

        if ($metainfo['video'] != false && $metainfo['audio'] != false) {
            return $this::MEDIA_TYPE_VIDEO;
        }
        return $this::MEDIA_TYPE_UNKNOWN;
    }

    private function finalizeEpisode($episode) {
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
            ],
            false,
            true
        );
    }

    /**
     * extracts streaminformations of an episode from its video.
     * returns false if informations can't be extracted
     * returns array of video and audio info.
     */
    private function getStreamInformationsOfEpisode($episode) {
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
                if (!array_key_exists('duration', $metadata['video'])) {
                    $metadata['video']['duration'] = 0;
                }
            }
        }

        return $metadata;
    }

    public function createNewCacheAssetForResolution($episode, $resolution) {
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

    private function deleteOriginalIfConfigured() {
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

    public function getName() {
        return 'Encode Video';
    }
}
