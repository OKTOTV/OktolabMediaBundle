<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EpisodeLifecycleEvent extends Event
{
    private $episode;

    public function __construct($episode)
    {
        $this->episode = $episode;
    }

    public function getEpisode()
    {
        return $this->episode;
    }
}

 ?>
