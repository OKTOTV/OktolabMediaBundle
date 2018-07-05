<?php

namespace Oktolab\MediaBundle\Twig;

class EpisodeDurationExtension extends \Twig_Extension
{
    public function getFilters() {
        return [
            new \Twig_SimpleFilter('duration', [$this,'duration'])
        ];
    }

    public function duration($duration)
    {
        $hours = floor($duration/3600);
        $minutes = floor(($duration - $hours*3600)/60);
        $seconds = ($duration - $hours*3600 - $minutes*60);

        if ($hours) {
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }
        return sprintf("%02d:%02d", $minutes, $seconds);
    }

    public function getName() {
        return 'oktolab_media_episode_duration_extension';
    }
}
