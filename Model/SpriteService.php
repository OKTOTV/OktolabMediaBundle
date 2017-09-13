<?php

namespace Oktolab\MediaBundle\Model;

class SpriteService {

    private $asset_helper;
    private $sprite_height;
    private $sprite_width;
    private $sprite_interval;

    public function __construct($asset_helper, $sprite_height, $sprite_width, $sprite_interval)
    {
        $this->asset_helper = $asset_helper;
        $this->sprite_height = $sprite_height;
        $this->sprite_width = $sprite_width;
        $this->sprite_interval = $sprite_interval;
    }

    /**
    * returns webvtt formated string for webplayers to display thumbnails
    */
    public function getSpriteWebvttForEpisode($episode, $player_type = "jwplayer")
    {
        $this->calculateInterval($episode);
        switch ($player_type) {
            case 'jwplayer':
                return $this->getSpriteWebvttForJwPlayer($episode);
                break;

            default:
                return $this->getSpriteWebvttForJwPlayer($episode);
                break;
        }
    }

    private function getSpriteWebvttForJwPlayer($episode)
    {
        if (!$episode->getSprite() || !$episode->getDuration()) {
            return "";
        }
        $link = $this->asset_helper->getAbsoluteUrl($episode->getSprite());
        $numberOfSprites = ceil($episode->getDuration()/$this->sprite_interval);

        $webvtt = "WEBVTT\n\n";
        for ($i = 0; $i < $numberOfSprites; $i++) {
            $begin = gmdate("H:i:s", $this->sprite_interval*$i);
            $end = gmdate("H:i:s", $this->sprite_interval*($i+1));
            $webvtt = sprintf(
                "%s%s --> %s\n%s#xywh=%s,%s,%s,%s\n\n",
                $webvtt,    //track till now
                $begin,     // webvtt timestamp from
                $end,       // webvtt timestamp to
                $link,      // absolute url to sprite
                0,          // x offset in sprite
                $this->sprite_height*$i, // y offset in sprite
                $this->sprite_width,     // width in sprite
                $this->sprite_height     // height in sprite
            );
        }

        return $webvtt;
    }

    // calculates the interval time for the length of an episode and considers jpeg dimension limit of 65500 px.
    private function calculateInterval($episode)
    {
        $max_image = floor(65500/$this->sprite_height);
        $calculated_interval = ceil($episode->getDuration()/$max_image);
        if ($calculated_interval > $this->sprite_interval) {
            $this->sprite_interval = $calculated_interval;
        }
    }
}
?>
