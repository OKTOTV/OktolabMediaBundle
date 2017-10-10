<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\AppLinkBundle\Entity\Keychain;
use Bprs\AppLinkBundle\Entity\Key;
use GuzzleHttp\Client;
use Oktolab\MediaBundle\Entity\Episode;

use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\ImportedEpisodeMetadataEvent;
use Oktolab\MediaBundle\Event\ImportedEpisodePosterframeEvent;
use Oktolab\MediaBundle\Event\ImportedSeriesPosterframeEvent;
use Oktolab\MediaBundle\Event\FinalizedEpisodeEvent;
use Oktolab\MediaBundle\Event\ImportedSeriesMetadataEvent;
use Oktolab\MediaBundle\Event\EpisodeAssetDataEvent;

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
    private $dispatcher; // event dispatcher
    private $worker_queue;

    public function __construct($jobService, $entity_manager, $serializer, $episode_class, $series_class, $asset_class, $adapters, $applinkservice, $logbook, $dispatcher, $worker_queue)
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
        $this->worker_queue = $worker_queue;
    }

    public function addEncodeEpisodeJob($uniqID, $worker_queue = false, $first = false)
    {
        if (!$worker_queue) {
            $worker_queue = $this->worker_queue;
        }
        $this->setEpisodeStatus($uniqID, Episode::STATE_IN_PROGRESS_QUEUE);
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\EncodeEpisodeJob",
            ['uniqID' => $uniqID],
            $worker_queue,
            $first
        );
    }

    /**
    * starts an import worker for an episode by uniqID from the given Keychain
    */
    public function addEpisodeJob(Keychain $keychain, $uniqID, $overwrite = false, $worker_queue = false ,$first = false)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeMetadataJob",
            [
                'keychain' => $keychain->getUniqID(),
                'uniqID' => $uniqID,
                'overwrite' => $overwrite
            ],
            $worker_queue,
            $first
        );
    }

    public function addImportEpisodeFileFromUrlJob($uniqID, $url, $worker_queue = false, $first = false)
    {
        if (!$worker_queue) {
            $worker_queue = $this->worker_queue;
        }
        $this->setEpisodeStatus($uniqID, Episode::STATE_IN_PROGRESS_QUEUE);
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeFileFromUrlJob",
            [
                'uniqID' => $uniqID,
                'url' => $url
            ],
            $worker_queue,
            $first
        );
    }

    /**
    * starts an import worker for a series by uniqID from the given Keychain
    */
    public function addSeriesJob(Keychain $keychain, $uniqID, $overwrite = false, $worker_queue = false , $first = false)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesMetadataJob",
            [
                'keychain' => $keychain->getUniqID(),
                'uniqID' => $uniqID,
                'overwrite' => $overwrite
            ],
            $worker_queue,
            $first
        );
    }

    public function addEpisodeAssetDataJob($uniqID, $worker_queue = false, $first = false)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\EpisodeAssetDataJob",
            ['uniqID' => $uniqID],
            $worker_queue,
            $first
        );
    }

    public function addGenerateThumbnailSpriteJob($uniqID, $worker_queue = false, $first = false)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\GenerateThumbnailSpriteJob",
            ['uniqID' => $uniqID],
            $worker_queue,
            $first
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
        return $this->em->getRepository($this->episode_class)->findByUniqID(
            $this->episode_class,
            $uniqID
        );
    }

    public function createEpisode()
    {
        return new $this->episode_class;
    }

    public function getEpisodeRepository()
    {
        return $this->em->getRepository($this->episode_class);
    }


    public function getSeries($uniqID)
    {
        return $this->em->getRepository($this->series_class)->findByUniqID(
            $this->series_class,
            $uniqID
        );
    }

    public function getSeriesRepo()
    {
        return $this->em->getRepository($this->series_class);
    }

    public function getCaption($uniqID)
    {
        return $this->em->getRepository('OktolabMediaBundle:Caption')
            ->findOneBy(['uniqID' => $uniqID]);
    }

    public function createSeries()
    {
        return new $this->series_class;
    }

    public function getAvailableRoles()
    {
        return [$this::ROLE_READ, $this::ROLE_WRITE];
    }

    /**
    * @deprecated
    * use addImportEpisodePosterframeJob in the future
    */
    public function addImportEpisodePosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodePosterframeJob",
            [
                'uniqID' => $uniqID,
                'keychain' => $keychain->getUniqID(),
                'key' => $filekey
            ]
        );
    }

    /**
     * please use addSeriesPosterframeJob in the future.
     * @deprecated
     */
    public function addImportSeriesPosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesPosterframeJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUniqID(), 'key' => $filekey]
        );
    }

    public function addEpisodePosterframeJob($uniqID, $queue = false)
    {
        if (!$queue) {
            $queue = $this->worker_queue;
        }

        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\EpisodePosterframeJob",
            ['uniqID' => $uniqID],
            $queue
        );
    }

    public function addSeriesPosterframeJob($uniqID, $queue = false)
    {
        if (!$queue) {
            $queue = $this->worker_queue;
        }

        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\SeriesPosterframeJob",
            ["uniqID" => $uniqID],
            $queue
        );
    }

    public function addImportEpisodeVideoJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeVideoJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUniqID(), 'key' => $filekey]
        );
    }

    public function dispatchImportedEpisodeMetadataEvent($args)
    {
        $event = new ImportedEpisodeMetadataEvent($args);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_EPISODE_METADATA, $event);
    }

    public function dispatchImportedSeriesMetadataEvent($args)
    {
        $event = new ImportedSeriesMetadataEvent($args);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_SERIES_METADATA, $event);
    }

    public function dispatchImportedEpisodePosterframeEvent($args)
    {
        $event = new ImportedEpisodePosterframeEvent($args);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_EPISODE_POSTERFRAME, $event);
    }

    public function dispatchImportedSeriesPosterframeEvent($args)
    {
        $event = new ImportedSeriesPosterframeEvent($args);
        $this->dispatcher->dispatch(OktolabMediaEvent::IMPORTED_SERIES_POSTERFRAME, $event);
    }

    public function dispatchFinalizedEpisodeEvent($args)
    {
        $event = new FinalizedEpisodeEvent($args);
        $this->dispatcher->dispatch(OktolabMediaEvent::FINALIZED_EPISODE, $event);
    }

    public function dispatchEpisodeAssetDataEvent($assetData)
    {
        $event = new EpisodeAssetDataEvent($assetData);
        $this->dispatcher->dispatch(OktolabMediaEvent::EPISODE_ASSETDATA, $event);
    }
}
