<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\AppLinkBundle\Entity\Keychain;
use Bprs\AppLinkBundle\Entity\Key;
use GuzzleHttp\Client;
use Oktolab\MediaBundle\Entity\Episode;

use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\ImportedEpisodeMetadataEvent;
use Oktolab\MediaBundle\Event\ImportedEpisodePosterframeEvent;

/**
 * TODO: use standardized links with applinkbundle (applink_helperservice)
 * use logging with logbook-bundle
 *
 */

/**
* handles import worker, jobs for worker and permission handling
*/
class MediaService
{
    const ROLE_READ = "ROLE_OKTOLAB_MEDIA_READ";
    const ROLE_WRITE = "ROLE_OKTOLAB_MEDIA_WRITE";

    const ROUTE_EPISODE = "oktolab_media_api_show_episode";
    const ROUTE_SERIES = "oktolab_media_api_show_series";
    const ROUTE_LIST_SERIES = "oktolab_media_api_list_series";
    const ROUTE_ASSET = "oktolab_media_api_show_asset";

    const ROUTE_EMBED_EPISODE = "oktolab_media_embed_episode";

    private $jobService; // triggers jobs for the workers
    private $em; // entity manager
    private $serializer; // json -> object
    private $episode_class; // your episode class
    private $series_class; // your series class
    private $asset_class; // the asset class
    private $adapters; // the adapter paths to save the assets to
    private $applinkservice; // service for api urls
    private $logbook; // loggingservice
    private $dispatcher;

    public function __construct($jobService, $entity_manager, $serializer, $episode_class, $series_class, $asset_class, $adapters, $applinkservice, $logbook, $dispatcher)
    {
        $this->jobService = $jobService;
        $this->em = $entity_manager;
        $this->serializer = $serializer;
        $this->episode_class = $episode_class;
        $this->series_class = $series_class;
        $this->asset_class= $asset_class;
        $this->adapters = $adapters;
        $this->applinkservice = $applinkservice;
        $this->logbook = $logbook;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @deprecated
     */
    public function encodeEpisode($uniqID)
    {
        $this->addEncodeVideoJob($uniqID);
    }

    public function addEncodeVideoJob($uniqID)
    {
        $this->setEpisodeStatus($uniqID, Episode::STATE_IN_PROGRESS_QUEUE);
        $this->jobService->addJob("Oktolab\MediaBundle\Model\EncodeVideoJob", ['uniqID' => $uniqID]);
    }

    /**
    * starts an import worker for an episode by uniqID from the given Keychain
    */
    public function addEpisodeJob(Keychain $keychain, $uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeMetadataJob",
            ['keychain' => $keychain->getUniqID(), 'uniqID' => $uniqID]
        );
    }

    /**
    * starts an import worker for a series by uniqID from the given Keychain
    */
    public function addSeriesJob(Keychain $keychain, $uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesJob",
            array('keychain' => $keychain->getUniqID(), 'uniqID' => $uniqID)
        );
    }

    public function addReadPermission(Keychain $keychain)
    {
        if (!in_array($this::ROLE_READ, $keychain->getRoles())) {
            $key = new Key();
            $key->setRole($this::ROLE_READ);
            $key->setKeychain($keychain);
            $keychain->addKey($key);

            $this->em->persist($key);
            $this->em->persist($keychain);
            $this->em->flush();
        }
    }

    public function addWritePermission(Keychain $keychain)
    {
        if (!in_array($this::ROLE_WRITE, $keychain->getRoles())) {
            $key = new Key();
            $key->setRole($this::ROLE_READ);
            $key->setKeychain($keychain);
            $keychain->addKey($key);

            $this->em->persist($key);
            $this->em->persist($keychain);
            $this->em->flush();
        }
    }

    public function getResponse($keychain, $route, array $query)
    {
        $url = $this->applinkservice->getApiUrlsForKey($keychain, $route);
        $client = new Client();
        $response = $client->request(
            'GET',
            $url,
            [
                'auth'  => [$keychain->getUser(), $keychain->getApiKey()],
                'query' => $query
            ]
        );
        return $response;
    }

    public function setEpisodeStatus($uniqID, $status)
    {
        $episode = $this->getEpisode($uniqID);
        if ($episode) {
            $episode->setTechnicalStatus($status);
            $this->em->persist($episode);
            $this->em->flush();
        }
    }

    public function getEpisode($uniqID)
    {
        return $this->em->getRepository($this->episode_class)->findByUniqID($this->episode_class, $uniqID);
    }

    public function createEpisode()
    {
        return new $this->episode_class;
    }

    public function getSeries($uniqID)
    {
        return $this->em->getRepository($this->series_class)->findByUniqID($this->series_class, $uniqID);
    }

    public function createSeries()
    {
        return new $this->series_class;
    }

    public function getAvailableRoles()
    {
        return [$this::ROLE_READ, $this::ROLE_WRITE];
    }

    public function addImportEpisodePosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodePosterframeJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUniqID(), 'key' => $filekey]
        );
    }

    public function addImportSeriesPosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesPosterframeJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUniqID(), 'key' => $filekey]
        );
    }

    public function addImportEpisodeVideoJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeVideoJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUniqID(), 'key' => $filekey]
        );
    }

    public function dispatchImportedEpisodeMetadataEvent($uniqID)
    {
        $event = new ImportedEpisodeMetadataEvent($uniqID);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_EPISODE_METADATA, $event);
    }

    public function dispatchImportedEpisodePosterframeEvent($uniqID)
    {
        $event = new ImportedEpisodePosterframeEvent($uniqID);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_EPISODE_POSTERFRAME, $event);
    }

    public function dispatchFinalizedEpisodeEvent($uniqID)
    {
        $event = new FinalizeEpisodeEvent($uniqID);
        $this->dispatcher->dispatch(OktolabMediaEvent::FINALIZED_EPISODE, $event);
    }
}
