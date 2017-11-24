<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EnqueuedEncodeEpisodeEvent extends Event
{
    protected $args;

    public function __construct($args) {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
