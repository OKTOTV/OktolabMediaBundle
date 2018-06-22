<?php

namespace Oktolab\MediaBundle\Twig;

class EpisodeDurationExtension extends \Twig_Extension
{
    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('duration', [$this,'duration'])
        );
    }

    public function duration($duration)
    {
        $hours = floor($duration/3600);
        $minutes = floor(($duration - $hours*3600)/60);
        $seconds = ($duration - $hours*3600 - $minutes*60);

        if ($hours) {
            return sprintf("%s:%s:%s", $hours, $minutes, $seconds);
        }
        return sprintf("%s:%s", $minutes, $seconds);
    }

    public function getName() {
        return 'oktolab_media_episode_duration_extension';
    }
}
