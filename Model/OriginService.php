<?php

namespace Oktolab\MediaBundle\Model;

use GuzzleHttp\Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OriginService {

    private $media_service;
    private $router;
    private $applink;

    public function __construct($media, $applink, $router)
    {
        $this->media_service = $media;
        $this->applink = $applink;
        $this->router = $router;
    }

    public function getOrigin($uniqID, $player_type = 'jwplayer')
    {
        $episode = $this->media_service->getEpisode($uniqID);
        $url = null;
        if ($episode->getKeychain()) { // episode is remote

            $url = $this->applink->getApiUrlsForKey(
                $episode->getKeychain(),
                'oktolab_media_origin_for_episode'
            );
            if (!$url) { //no remote origin available, use own
                $url = $this->router->generate(
                    'oktolab_media_origin_for_episode',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
        } else {
            $url = $this->router->generate(
                'oktolab_media_origin_for_episode',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        $client = new Client();
        $response = $client->request(
            'GET',
            $url
        );

        $origin = json_decode($response->getBody());
        return $origin;
    }
}
