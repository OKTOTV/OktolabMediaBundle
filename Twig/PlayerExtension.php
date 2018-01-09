<?php

namespace Oktolab\MediaBundle\Twig;

class PlayerExtension extends \Twig_Extension
{
    private $twig;
    private $player_url;
    private $origin;

    public function __construct($twig, $player_url, $default_player, $origin)
    {
        $this->twig = $twig;
        $this->player_url = $player_url;
        $this->default_player = $default_player;
        $this->origin = $origin;
    }


    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('player', [$this,'player']),
            new \Twig_SimpleFunction('playlist', [$this,'playlist']),
            new \Twig_SimpleFunction('origin', [$this, 'origin']),
            new \Twig_SimpleFunction('playerUrl', [$this, 'playerUrl'])
        );
    }

    public function player($episode, $player_id = "player", $player_type = false, $displaytitle = false)
    {
        if (!$player_type) {
            $player_type = $this->default_player;
        }
        return $this->getPlayerForType($episode, $player_id, $player_type, $displaytitle);
    }

    public function playlist($playlist, $player_id = "player", $player_type = false, $displaytitle = false)
    {
        if (!$player_type) {
            $player_type = $this->default_player;
        }
        return $this->getPlaylistPlayerForType($playlist, $player_id, $player_type, $displaytitle);
    }

    public function origin($episode, $player_type = false)
    {
        if (!$player_type) {
            $player_type = $this->default_player;
        }
        return $this->getOriginForType($episode, $player_type);
    }

    public function playerUrl()
    {
        return $this->player_url;
    }

    private function getPlayerForType($episode, $player_id, $player, $displaytitle = false)
    {
        switch ($player) {
            case 'jwplayer':
                return $this->twig->render('OktolabMediaBundle:Player:jwplayer.js.twig',
                    [
                        'episode' => $episode,
                        'player_url' => $this->player_url,
                        'player_id' => $player_id,
                        'displaytitle' => $displaytitle
                    ]
                );
            default:
                return $this->twig->render('OktolabMediaBundle:Player:jwplayer.js.twig',
                    [
                        'episode' => $episode,
                        'player_url' => $this->player_url,
                        'player_id' => $player_id,
                        'displaytitle' => $displaytitle
                    ]
                );
        }
    }

    private function getPlaylistPlayerForType($playlist, $player_id, $player_type = 'jwplayer', $displaytitle = false)
    {
        switch ($player_type) {
            case 'jwplayer':
                return $this->twig->render('OktolabMediaBundle:Player:playlist_jwplayer.js.twig',
                    [
                        'playlist' => $playlist,
                        'player_url' => $this->player_url,
                        'player_id' => $player_id,
                        'displaytitle' => $displaytitle
                    ]
                );
            default:
            return $this->twig->render('OktolabMediaBundle:Player:playlist_jwplayer.js.twig',
                [
                    'playlist' => $playlist,
                    'player_url' => $this->player_url,
                    'player_id' => $player_id,
                    'displaytitle' => $displaytitle
                ]
            );
        }
    }

    private function getOriginForType($episode, $player_type = 'jwplayer')
    {
        switch ($player_type) {
            case 'jwplayer':
                $origin = $this->origin->getOrigin($episode->getUniqID(), $player_type);
                return $this->twig->render(
                    'OktolabMediaBundle:Public_Api:origin_jwplayer.json.twig',
                    ['origin' => $origin]
                );
        }
    }

    public function getName() {
        return 'oktolab_media_player_extension';
    }
}
