<?php

namespace Oktolab\MediaBundle\Model;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

class KeychainService {

    private $applink_service;
    private $jms_serializer;
    private $serialization_group;
    private $series_class;
    private $episode_class;

    public function __construct($applink_service, $serializer, $serializer_group, $series_class, $episode_class)
    {
        $this->applink_service = $applink_service;
        $this->jms_serializer = $serializer;
        $this->serialization_group = $serializer_group;
        $this->series_class = $series_class;
        $this->episode_class = $episode_class;
    }

    public function getSeriess($keychain, $url = null)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $url != null ? $url : $this->applink_service->getApiUrlsForKey($keychain, 'oktolab_media_api_list_series'),
            [
                'auth' => [$keychain->getUser(), $keychain->getApiKey()]
            ]
        );
        return json_decode($response->getBody());
    }

    public function getSeries($keychain, $uniqID)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->applink_service->getApiUrlsForKey($keychain, 'oktolab_media_api_show_series'),
            [
                'auth' => [$keychain->getUser(), $keychain->getApiKey()],
                'query'=> ['uniqID' => $uniqID, 'group' => $this->serialization_group]
            ]
        );

        $series = $this->jms_serializer->deserialize($response->getBody(), $this->series_class, 'json');
        return $series;
    }

    public function getEpisode($keychain, $uniqID)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->applink_service->getApiUrlsForKey($keychain, 'oktolab_media_api_show_episode'),
            [
                'auth' => [$keychain->getUser(), $keychain->getApiKey()],
                'query'=> ['uniqID' => $uniqID, 'group' => $this->serialization_group]
            ]
        );

        $episode = $this->jms_serializer->deserialize($response->getBody(), $this->episode_class, 'json');
        return $episode;
    }

    public function exportSeries($keychain, $uniqID, $overwrite = false)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->applink_service->getApiUrlsForKey($keychain, 'oktolab_media_api_import_series'),
            [
                'auth' => [$keychain->getUser(), $keychain->getApiKey()],
                'query' => ['uniqID' => $uniqID, 'overwrite' => $overwrite]
            ]
        );

        if ($response->getStatusCode() == Response::HTTP_ACCEPTED) {
            return true;
        }

        return false;
    }

    public function exportEpisode($keychain, $uniqID, $overwrite = false)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->applink_service->getApiUrlsForKey($keychain, 'oktolab_media_api_import_episode'),
            [
                'auth' => [$keychain->getUser(), $keychain->getApiKey()],
                'query' => ['uniqID' => $uniqID, 'overwrite' => $overwrite]
            ]
        );

        if ($response->getStatusCode() == Response::HTTP_ACCEPTED) {
            return true;
        }

        return false;
    }
}
