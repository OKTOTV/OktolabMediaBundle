<?php

namespace Oktolab\MediaBundle\Model;

class FFmpegService {

    private $asset_helper;
    private $logbook;
    private $em;

    public function __construct($asset_helper, $logbook, $em)
    {
        $this->asset_helper = $asset_helper;
        $this->logbook = $logbook;
        $this->em = $em;
    }

    public function executeFFmpegForMedia($encoder, $flags, $media)
    {
        $uri = $this->asset_helper->getAbsoluteUrl($media->getEpisode()->getVideo());

        if ($uri) {
            $cmd = sprintf(
                '%s -i "%s" %s "%s" 2>&1',
                $encoder,
                $uri,
                $flags,
                $this->asset_helper->getPath($media->getAsset(), true)
            );

            $this->logbook->info(
                'oktolab_media.episode_start_saving_media',
                [
                    '%cmd%'    => $cmd,
                    '%format%' => $media->getQuality()
                ],
                $media->getEpisode()->getUniqID()
            );

            $this->runCommand($cmd, $media);

        } else {
            $this->logbook->info(
                'oktolab_media.ffmpeg_service_no_uri_for_episode',
                [],
                $episode->getUniqID()
            );
        }
    }

    private function runCommand($cmd, $media)
    {
        $durationInSeconds = $media->getEpisode()->getDuration();
        // open (ffmpeg) process, read stdout
        $fp = popen($cmd, "r");
        while(!feof($fp)) {
            // read outputstream of ffmpeg
            $chunk = fread($fp, 1024);

            // try to get the duration information at the beginning of the ffmpeg output.
            // it is important to know how long an episode is for future jobs and percentage calculations.
            if (!$durationInSeconds) {
                preg_match("/Duration: (.*?), start:/", $chunk, $matches);
                if (array_key_exists(1, $matches)) {
                    list($hours,$minutes,$seconds) = explode(":",$matches[1]);
                    // calculate the duration in seconds. Used to calculate overall progress in percent.
                    $durationInSeconds = (($hours * 3600) + ($minutes * 60) + $seconds);
                    $episode->setDuration($durationInSeconds);
                    $this->em->persist($episode);
                    $this->em->flush();
                }
            }

            // try to get the current encoding information of ffmpeg
            preg_match("/time=(.*?) bitrate/", $chunk, $progress);
            if (array_key_exists(1, $progress)) {
                list($hours,$minutes,$seconds) = explode(":",$progress[1]);
                $seconds = round(($hours * 3600) + ($minutes * 60) + $seconds);

                // calculate percentage using the duration in seconds and current second
                if ($durationInSeconds) {
                    $percent = round((($seconds * 100)/($durationInSeconds)));
                    if ($percent > $media->getProgress()) {
                        // disable rounding errors to show 101 percent encoding
                        if ($percent > 100) {
                            $percent = 100;
                        }
                        // update information of the media. happens every percentage update
                        $media->setProgress($percent);
                        $this->em->persist($media);
                        $this->em->flush();
                    }
                }
            }
            // flush the content to the browser
            flush();
        }
        fclose($fp);
    }

}
