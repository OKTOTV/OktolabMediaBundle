<?php

namespace Oktolab\MediaBundle\Model;

/**
 * allows checks of streams for media encoding with ffprobe
 */
class FFprobeService {

    // Encoding Options for file and resolution
    const ENCODING_OPTION_NONE             = 0;   // no reliable information in file found

    // AUDIOFILES
    const ENCODING_OPTION_AUDIO_CONVERT    = 30;  // audio file! stream must be encoded
    const ENCODING_OPTION_AUDIO_COPY       = 40;  // audio file! stream can be copied

    // VIDEOFILES
    const ENCODING_OPTION_VIDEO_ONLY_ENCODE = 50; // video file without audio. the video stream must be encoded for this resolution
    const ENCODING_OPTION_VIDEO_ONLY_COPY  = 60;  // video file without audio. but the video can be copied
    const ENCODING_OPTION_VIDEO_ENCODE_BOTH = 70; // audio and video must be converted
    const ENCODING_OPTION_VIDEO_COPY_AUDIO = 80;  // the video must be converted, audio can be copied
    const ENCODING_OPTION_VIDEO_COPY_VIDEO = 90;  // the video stream can be copied, audio must be converted
    const ENCODING_OPTION_VIDEO_COPY_ALL   = 100; // the video and auido stream can be copied for this resolution

    const MEDIA_TYPE_UNKNOWN        = 0; // unknown mediatype (or not yet supported)
    const MEDIA_TYPE_AUDIO          = 10; // mediatype seems to be a soundfile
    const MEDIA_TYPE_VIDEO_ONLY     = 15; // mediatype seems to be a videofile with no audiotrack
    const MEDIA_TYPE_VIDEO          = 20; // mediatype seems to be a videofile

    public function __construct($bprs_asset_helper)
    {
        $this->bprs_asset_helper = $bprs_asset_helper;
    }

    /**
     * returns
     */
    public function detectEncodingOptionForResolution($resolution, $episode)
    {
        $metainfo = $this->getMetainfoForEpisode($episode);
        switch ($this->getMediaType($metainfo)) {
            case $this::MEDIA_TYPE_AUDIO:
                $can_copy_audio = $this->audioCanBeCopied(
                    $resolution,
                    $metainfo['audio']
                );
                $can_encode_audio = $this->audioCanBeEncoded(
                    $resolution,
                    $metainfo['audio']
                );
                if ($can_copy_audio) {
                    return $this::ENCODING_OPTION_AUDIO_COPY;
                }

                if ($can_encode_audio) {
                    return $this::ENCODING_OPTION_AUDIO_CONVERT;
                }
                return $this::ENCODING_OPTION_NONE;

            case $this::MEDIA_TYPE_VIDEO:
                $can_copy_audio = $this->audioCanBeCopied(
                    $resolution,
                    $metainfo['audio']
                );
                $can_encode_audio = $this->audioCanBeEncoded(
                    $resolution,
                    $metainfo['audio']
                );
                $can_copy_video = $this->videoCanBeCopied(
                    $resolution,
                    $metainfo['video']
                );
                $can_encode_video = $this->videoCanBeEncoded(
                    $resolution,
                    $metainfo['video']
                );

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

            case $this::MEDIA_TYPE_VIDEO_ONLY:
                $can_copy_video = $this->videoCanBeCopied(
                    $resolution,
                    $metainfo['video']
                );
                $can_encode_video = $this->videoCanBeEncoded(
                    $resolution,
                    $metainfo['video']
                );

                if ($can_copy_video) {
                    return $this::ENCODING_OPTION_VIDEO_ONLY_COPY;
                } elseif ($can_encode_video) {
                    return $this::ENCODING_OPTION_VIDEO_ONLY_ENCODE;
                }
                return $this::ENCODING_OPTION_NONE;
        }
        return $this::ENCODING_OPTION_NONE;
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
         return
             // sample rate of file is better or as good as defined in resolution
             $resolution['audio_sample_rate'] >= $metainfo['sample_rate'] &&
             // bitrate of file is better or as good as defined in resolution
             $resolution['audio_bitrate'] <= $metainfo['max_bit_rate'] ||
             // or we allow worse quality to be used for encoding
             filter_var(
                 $resolution['allow_lower_audio_bitrate'],
                 FILTER_VALIDATE_BOOLEAN
             )
         ;
     }

    /**
     * returns the mediatype depending on given metainfos
     * MEDIA_TYPE_AUDIO if soundfile,
     * MEDIA_TYPE_VIDEO if videofile,
     * MEDIA_TYPE_VIDEO_ONLY if videofile without soundstream.
     */
    private function getMediaType($metainfo) {
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

    /**
     * extracts streaminformations of an episode from its video.
     * @param object $episode Entity you want the stream infos from
     * @return false|array if informations can't be extracted,
     * array of video and audio info
     */
    private function getMetainfoForEpisode($episode) {
        $uri = $this->bprs_asset_helper->getAbsoluteUrl(
            $episode->getVideo()
        );
        if (!$uri) { // can't create uri of episode video
            $this->logbook->error(
                'oktolab_media.episode_encode_no_streams',
                [],
                $episode->getUniqID()
            );
            return false;
        }

        $metainfo = json_decode(
            shell_exec(
                sprintf(
                    'ffprobe -v error -show_streams -print_format json %s',
                    $uri)
                ),
            true
        );
        $metadata = ['video' => false, 'audio' => false];

        // run through all streams to find the primary video and audio stream
        foreach ($metainfo['streams'] as $stream) {
            // found both streams, skip the rest
            if ($metadata['video'] && $metadata['audio']) {
                break;
            }

            // found an audio stream
            if ($stream['codec_type'] == "audio" && $metadata['audio'] == false) {
                $metadata['audio'] = $stream;
                if (
                    !array_key_exists('max_bit_rate', $metadata['audio']) &&
                    array_key_exists('tags', $metadata['audio'])
                ) {
                    $metadata['audio']['max_bit_rate'] = $metadata['tags']['BPS'];
                }
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
}
