<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DeleteSeriesEvent extends Event
{
    private $series;

    public function __construct($series)
    {
        $this->series = $series;
    }

    public function getSeries()
    {
        return $this->series;
    }
}

 ?>
