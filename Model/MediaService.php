<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\AppLinkBundle\Entity\Keychain;
use Bprs\AppLinkBundle\Entity\Key;
use Oktolab\MediaBundle\Entity\Asset;

/**
* handles import worker, jobs for worker and permission handling
*/
class MediaService
{
    const ROLE_READ = "ROLE_OKTOLAB_MEDIA_READ";
    const ROLE_WRITE = "ROLE_OKTOLAB_MEDIA_WRITE";

    private $jobService; // triggers jobs for the workers
    private $em; // entity manager
    private $serializer; // json -> object
    private $episode_class; // your episode class
    private $series_class; // your series class
    private $asset_class; // the asset class
    private $adapters; // the adapter paths to save the assets to

    public function __construct($jobService, $entity_manager, $serializer, $episode_class, $series_class, $asset_class, $adapters)
    {
        $this->jobService = $jobService;
        $this->em = $entity_manager;
        $this->serializer = $serializer;
        $this->episode_class = $episode_class;
        $this->series_class = $series_class;
        $this->asset_class= $asset_class;
        $this->adapters = $adapters;
    }

    public function addEncodeVideoJob($uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\EncodeVideoJob",
            ['uniqID' => $uniqID]
        );
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

    public function encodeEpisode($uniqID)
    {
        $this->jobService->addJob("Oktolab\MediaBundle\Model\EncodeVideoJob", ['uniqID' => $uniqID]);
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

    /**
     * loads and deserializes remote episode,
     * merges metadata
     * imports assets
     */
    public function importEpisode(Keychain $keychain, $uniqID, $flush = true)
    {
        $client = new GuzzleHttp\Client();
        $response = $client->request('GET',
            $keychain->getUrl().'/api/oktolab_media/episode/'.$uniqID,
            ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
        );
        if ($response->getStatusCode() == 200) {
            $episode = $this->serializer->deserialize($response->getBody(), $this->episode_class, 'json');
            $local_episode = $this->em->getRepository($this->episode_class)->findOneBy(array('uniqID' => $uniqID));
            $local_series = $this->em->getRepository($this->series_class)->findOneBy(array('uniqID' => $episode->getSeries()->getUniqID()));
            if (!$local_series) {
                $local_series = new $this->series_class;
            }
            if (!$local_episode) {
                $local_episode = new $this->episode_class;
            }
            $local_episode->merge($episode);
            $local_series->merge($episode->getSeries());
            $local_episode->setSeries($local_series);
            $local_series->addEpisode($local_episode);
            //TODO: remove hardcoded adapter names and replace them with a container parameter!
            $local_episode->setVideo($this->importAsset($keychain, $episode->getVideo(), 'video'));
            $local_episode->setPosterframe($this->importAsset($keychain, $episode->getPosterframe(), 'posterframe'));

            $this->em->persist($local_episode);
            $this->em->persist($local_series);

            if ($flush) {
                $this->em->flush();
                $this->em->clear();
            }

            $this->encodeEpisode($local_episode->getUniqID());
        } else {
            //something went wrong. Application not responding correctly
        }
    }

    /**
    * imports and returns asset
    */
    private function importAsset(Keychain $keychain, $key, $adapter)
    {
        if ($key) {
            echo "Importing key: --".$key."--\n";
            $url = $keychain->getUrl()."/api/oktolab_media/asset/json/".$key;
            $client = new GuzzleHttp\Client();
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
                        $keychain->getUrl().'/api/bprs_asset/download/'.$key, //$keychain->getUrl().'/api/oktolab_media/download/'.$key,
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
        $response = $this->guzzle->get(
            $keychain->getUrl().'/api/oktolab_media/series/'.$uniqID,
            ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
        );
        if ($response->getStatusCode() == 200) {
            $series = $this->serializer->deserialize($response->getBody(), $this->series_class, 'json');
            $local_series = $this->em->getRepository($this->series_class)->findOneBy(array('uniqID' => $uniqID));
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
                    //$this->importEpisode($keychain, $episode->getUniqID());
                    $uniqID = $episode->getUniqID();
                    $response = $this->guzzle->get(
                        $keychain->getUrl().'/api/oktolab_media/episode/'.$uniqID,
                        ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
                    );
                    if ($response->getStatusCode() == 200) {
                        $episode = $this->serializer->deserialize($response->getBody(), $this->episode_class, 'json');
                        $local_episode = $this->em->getRepository($this->episode_class)->findOneBy(array('uniqID' => $uniqID));
                        if (!$local_episode) {
                            $local_episode = new $this->episode_class;
                        }
                        $local_episode->merge($episode);
                        $local_episode->setSeries($local_series);
                        $local_series->addEpisode($local_episode);
                        $local_episode->setVideo($this->importAsset($keychain, $episode->getVideo(), 'video'));
                        $local_episode->setPosterframe($this->importAsset($keychain, $episode->getPosterframe(), 'gallery'));

                        $this->em->persist($local_episode);

                    } else {
                        //something went wrong. Application not responding correctly
                    }
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
}
