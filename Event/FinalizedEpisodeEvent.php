<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FinalizedEpisodeEvent extends Event
{
    protected $args;

    public function __construct($args) {
        $this->args = $args;
    }

    public function getUniqID()
    {
        return $this->args['uniqID'];
    }
}
