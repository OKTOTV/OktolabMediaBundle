<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EpisodeAssetDataEvent extends Event
{
    protected $metainformations;

    public function __construct($metainformations) {
        $this->metainformations = $metainformations;
    }

    public function getMetainformations()
    {
        return $this->metainformations;
    }
}
