<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\AppLinkBundle\Entity\Keychain;
use Bprs\AppLinkBundle\Entity\Key;
use GuzzleHttp\Client;
use Oktolab\MediaBundle\Entity\Episode;

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
    const ROUTE_ASSET = "oktolab_media_api_show_asset";

    private $jobService; // triggers jobs for the workers
    private $em; // entity manager
    private $serializer; // json -> object
    private $episode_class; // your episode class
    private $series_class; // your series class
    private $asset_class; // the asset class
    private $adapters; // the adapter paths to save the assets to
    private $applinkservice; // service for api urls
    private $logbook; // loggingservice

    public function __construct($jobService, $entity_manager, $serializer, $episode_class, $series_class, $asset_class, $adapters, $applinkservice, $logbook)
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
            "Oktolab\MediaBundle\Model\ImportEpisodeJob",
            array('user' => $keychain->getUser(), 'uniqID' => $uniqID)
        );
    }

    /**
    * starts an import worker for a series by uniqID from the given Keychain
    */
    public function addSeriesJob(Keychain $keychain, $uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesJob",
            array('user' => $keychain->getUser(), 'uniqID' => $uniqID)
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

    /**
     * loads and deserializes remote episode,
     * merges metadata
     * imports assets
     */
    public function importEpisode(Keychain $keychain, $uniqID, $flush = true)
    {
        $this->setEpisodeStatus($uniqID, Episode::STATE_IMPORTING);
        $client = new Client();
        $response = $this->getResponse($keychain, this::ROUTE_EPISODE, ['uniqID' => $uniqID]);
        if ($response->getStatusCode() == 200) {
            $episode = $this->serializer->deserialize($response->getBody(), $this->episode_class, 'json');
            $local_episode = $this->getEpisode($uniqID);
            $local_series = $this->em->getRepository($this->series_class)->findOneBy(['uniqID' => $episode->getSeries()->getUniqID()]);
            if (!$local_series) {
                $this->importSeries($keychain, $episode->getSeries()->getUniqID(), false);
            }
            if (!$local_episode) {
                $local_episode = new $this->episode_class;
            }
            $local_episode->merge($episode);
            $local_episode->setSeries($local_series);
            $local_series->addEpisode($local_episode);
            //TODO: remove hardcoded adapter names and replace them with a container parameter!
            $local_episode->setVideo($this->importAsset($keychain, $episode->getVideo(), 'video'));
            $local_episode->setPosterframe($this->importAsset($keychain, $episode->getPosterframe(), 'posterframe'));

            if (!$local_series->getPosterframe()) {
                $local_series->setPosterframe($this->importAsset($keychain, $episode->getSeries()->getPosterframe(), 'posterframe'));
            }


            $this->em->persist($local_episode);
            $this->em->persist($local_series);

            if ($flush) {
                $this->em->flush();
            }

            $this->addEncodeVideoJob($local_episode->getUniqID());
        } else {
            $this->setEpisodeStatus($uniqID, Episode::STATE_NOT_READY);
            //something went wrong. Application not responding correctly
        }
    }

    /**
    * imports and returns asset
    * step 1 wget file to cache fs, step 2: move to adapter fs
    */
    private function importAsset(Keychain $keychain, $key, $adapter)
    {
        if ($key) {
            echo "Importing key: --".$key."--\n";
            $url = $keychain->getUrl()."/api/oktolab_media/asset/json/".$key;
            $client = new Client();
            $response = $client->request('GET',
                $url, ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
            );
            if ($response->getStatusCode() == 200) {
                $remote_asset = json_decode($response->getBody());
                $asset = new $this->asset_class;
                $asset->setFilekey($key);
                $asset->setAdapter($adapter);
                $asset->setName($remote_asset->name);
                $asset->setMimetype($remote_asset->mimetype);
                shell_exec(
                    sprintf('wget --http-user=%s --http-password=%s %s --output-document=%s',
                        $keychain->getUser(),
                        $keychain->getApiKey(),
                        $keychain->getUrl().'/api/bprs_asset/download/'.$key,
                        $this->adapters[$adapter]['path'].'/'.$key
                    )
                );
                $this->em->persist($asset);
                return $asset;
            }
        }
    }

    public function importSeries(Keychain $keychain, $uniqID, $andEpisodes = true)
    {
        $client = new Client();
        $response = $client->request('GET',
            $keychain->getUrl().'/api/oktolab_media/series/'.$uniqID,
            ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
        );
        if ($response->getStatusCode() == 200) {
            $series = $this->serializer->deserialize($response->getBody(), $this->series_class, 'json');
            $local_series = $this->em->getRepository($this->series_class)->findOneBy(['uniqID' => $uniqID]);
            if (!$local_series) {
                $local_series = new $this->series_class;
            }
            $local_series->merge($series);
            $local_series->setPosterframe($this->importAsset($keychain, $series->getPosterframe(), 'gallery'));
            $this->em->persist($local_series);
            $this->em->flush();
            if ($andEpisodes) {
                gc_enable();
                foreach ($series->getEpisodes() as $episode) {
                    $this->addEpisodeJob($keychain, $episode->getUniqID());
                    unset($episode);
                }
                gc_disable();
            }
            $this->em->persist($local_series);
            $this->em->flush();
        } else {
            //something went wrong. Application not responding correctly
        }
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
        return $this->em->getRepository($this->episode_class)->findOneBy(['uniqID' => $uniqID]);
    }

    public function getSeries($uniqID)
    {
        return $this->em->getRepository($this->series_class)->findOneBy(['uniqID' => $uniqID]);
    }

    public function getAvailableRoles()
    {
        return [$this::ROLE_READ, $this::ROLE_WRITE];
    }

    public function addImportEpisodePosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodePosterframeJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUser(), 'key' => $filekey]
        );
    }

    public function addImportSeriesPosterframeJob($uniqID, $keychain, $filekey)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesPosterframeJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUser(), 'key' => $filekey]
        );
    }

    public function addImportEpisodeVideoJob($value='')
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeVideoJob",
            ['uniqID' => $uniqID, 'keychain' => $keychain->getUser(), 'key' => $filekey]
        );
    }
}
