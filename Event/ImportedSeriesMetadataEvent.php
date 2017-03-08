<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ImportedSeriesMetadataEvent extends Event
{
    protected $args;

    public function __construct($args) {
        $this->args = $args;
    }

    public function getUniqID()
    {
        return $this->args['uniqID'];
    }

    public function getArgs()
    {
        return $this->args;
    }
}
