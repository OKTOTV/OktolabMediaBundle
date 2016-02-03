<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Media;

//TODO: flexibility to encode audio only too!
//TODO: implement gaufrette filesystems correctly! only works with local files at this moment

/**
 * checks original video and starts encoding according to configurated resolutions.
 * after encoding, checks availability
 */
class EncodeVideoJob extends BprsContainerAwareJob
{
    public function perform() {
        print_r($this->getContainer()->getParameter('oktolab_media.episode_class'));
        $resolutions = $this->getContainer()->getParameter('oktolab_media.resolutions');

        $episode_class = $this->getContainer()->getParameter('oktolab_media.episode_class');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $episode = $em->getRepository($episode_class)->findOneBy(['uniqID' => $this->args['uniqID']]);
        $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());
        $adapters = $this->getContainer()->getParameter('bprs_asset.adapters');

        $metainfo = json_decode(shell_exec(sprintf('ffprobe -v error -show_streams -print_format json %s', $uri)), true);
        $metadata_video = null;
        $metadata_audio = null;

        foreach ($metainfo['streams'] as $stream) {
            if ($metadata_audio && $metadata_video) {
                break;
            }
            if ($stream['codec_type'] == "audio") {
                $metadata_audio = $stream;
            }
            if ($stream['codec_type'] == "video") {
                $metadata_video = $stream;
            }
        }

        foreach($resolutions as $format => $resolution) {
            // create new asset, path and key
            $class = $this->getContainer()->getParameter('oktolab_media.asset_class');
            $asset = new $class;
            $key = uniqId().'.'.$resolution['container'];
            $path = $adapters[$episode->getVideo()->getAdapter()]['path'].'/'.$key;
            $asset->setFilekey($key);
            $asset->setAdapter($episode->getVideo()->getAdapter());
            $asset->setName((string)$episode);
            $asset->setMimetype('video/quicktime');

            if ($this->resolutionIsTheSame($resolution, $metadata_video)) { //resolution is the same
                if ($this->videoCanBeCopied($resolution, $metadata_video)) { //videocodec is the same, can be copied
                    if ($this->audioCanBeCopied($resolution, $metadata_audio)) { // audiocodec is the same, can be copied
                        shell_exec(sprintf('ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a copy "%s"', $uri, $path));
                        $this->saveMedia($em, $format, $resolution, $asset, $episode);
                    } else { // just copy video
                        shell_exec(sprintf('ffmpeg -i "%s" -movflags +faststart -c:v copy -c:a aac -strict -2 "%s"', $uri, $path));
                        $this->saveMedia($em, $format, $resolution, $asset, $episode);
                    }
                } else { // video can not be copied (encode me)
                    shell_exec(sprintf('ffmpeg -i "%s" -deinterlace -crf 21 -movflags +faststart -c:v h264 -r 50 -c:a aac -strict -2 "%s"', $uri, $path));
                    $this->saveMedia($em, $format, $resolution, $asset, $episode);
                }
            } elseif ($this->resolutionCanBeEncoded($resolution, $metadata_video)) { // resolution can be encoded
                if ($this->audioCanBeCopied($resolution, $metadata_audio)) { // audiocodec is the same, can be copied
                    shell_exec(
                        sprintf(
                            'ffmpeg -i "%s" -deinterlace -crf 21 -s %sx%s -movflags +faststart -c:v h264 -r 50 -c:a copy "%s"',
                            $uri,
                            $resolution['video_width'],
                            $resolution['video_height'],
                            $path
                        )
                    );
                    $this->saveMedia($em, $format, $resolution, $asset, $episode);
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
                    $this->saveMedia($em, $format, $resolution, $asset, $episode);
                }
            }
        }

        if (!$this->getContainer()->getParameter('oktolab_media.keep_original')) {
            $this->getContainer()->get('asset_service')->deleteAsset($episode->getVideo());
            $best_media = $episode->getMedia()[0];
            foreach ($episode->getMedia() as $media) {
                if ($media->getSortNumber() > $best_media->getSortNumber()) {
                    $best_media = $media;
                }
            }
            $episode->setVideo($best_media);
        }
        //TODO: add finalize episode
        $this->finalizeEpisode($episode);
    }

    public function getName() {
        return 'Encode Video';
    }

    private function resolutionIsTheSame($resolution, $metadata_video)
    {
        return $resolution["video_width"] == $metadata_video['width'] && $resolution["video_height"] == $metadata_video['height'];
    }

    private function videoCanBeCopied($resolution, $metadata_video)
    {
        return $resolution['video_codec'] == $metadata_video['codec_name'] && $resolution['video_framerate'] == $metadata_video['avg_frame_rate'];
    }

    private function audioCanBeCopied($resolution, $metadata_audio)
    {
        return $resolution['audio_codec'] == $metadata_audio['codec_name'] && $resolution['audio_sample_rate'] == $metadata_audio['sample_rate'];
    }

    private function resolutionCanBeEncoded($resolution, $metadata_video)
    {
        return $resolution["video_width"] <= $metadata_video['width'] && $resolution["video_height"] <= $metadata_video['height'];
    }

    private function saveMedia($em, $format, $resolution, $asset, $episode)
    {
        $media = new Media();
        $media->setQuality($format);
        $media->setSortNumber($resolution['sortNumber']);
        $media->setAsset($asset);
        $media->setPublic($resolution['public']);
        $episode->addMedia($media);
        $em->persist($asset);
        $em->persist($media);
        $em->persist($episode);
        $em->flush();
    }

    private function finalizeEpisode($episode)
    {
        $is_active = true;
        $guzzle = $this->getContainer()->get('guzzle.client');
        $asset_helper = $this->getContainer()->get('asset_helper');
        foreach ($episode->getMedia() as $media) {
            $response = $guzzle->get($asset_helper->getAbsoluteUrl($media->getAsset()));
            if (!$response->getStatusCode() == 200) {
                $is_active = false;
            }
        }

        $episode->setIsActive($is_active);
        $this->em->persist($episode);
        $this->em->flush();
    }
}
