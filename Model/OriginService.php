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

    public function getOrigin($uniqID, $player_type)
    {
        // get keychain api url f.e. http://www.tidenet.de/asdf/ like
        $episode = $this->media_service->getEpisode($uniqID);
        $api_url = $this->router->generate('oktolab_media_origin_for_episode',
            [
                'player_type' => $player_type,
                '_format' => 'json'
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        if ($episode->getKeychain()) {
            $roles = $this->media_service->getAvailableRoles();
            $api_url = $this->applink->getApiUrlForKey($episode->getKeychain(), $roles);
            $api_url = $api_url.$this->router->generate(
                'oktolab_media_origin_for_episode',
                [
                    'player_type' => $player_type,
                    '_format' => 'json'
                ]
            );
        }

        $client = new Client();
        $response = $client->request('GET',
            $api_url
        );
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody());
        }
    }
}

?>
