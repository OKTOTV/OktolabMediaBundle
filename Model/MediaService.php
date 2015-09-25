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
    private $guzzle; // load json with login
    private $episode_class; // your episode class
    private $series_class; // your series class
    private $adapters; // the adapter paths to save the assets to

    public function __construct($jobService, $entity_manager, $serializer, $guzzle, $episode_class, $series_class, $adapters)
    {
        $this->jobService = $jobService;
        $this->em = $entity_manager;
        $this->serializer = $serializer;
        $this->guzzle = $guzzle;
        $this->episode_class = $episode_class;
        $this->series_class = $series_class;
        $this->adapters = $adapters;
    }

    /**
    * starts an import worker for an episode by uniqID from the given Keychain
    */
    public function addEpisodeJob(Keychain $keychain, $uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportEpisodeJob",
            array('url' => $keychain->getUrl(), 'uniqID' => $uniqID),
            "default"
        );
    }

    /**
    * starts an import worker for a series by uniqID from the given Keychain
    */
    public function addSeriesJob(Keychain $keychain, $uniqID)
    {
        $this->jobService->addJob(
            "Oktolab\MediaBundle\Model\ImportSeriesJob",
            array('url' => $keychain->getUrl(), 'uniqID' => $uniqID),
            "default"
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
        $response = $this->guzzle->get(
            $keychain->getUrl().'/api/oktolab_media/episode/'.$uniqID,
            ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
        );
        if ($response->getStatusCode() == 200) {
            $episode = $this->serializer->deserialize($response->getBody(), $this->episode_class, 'json');
            $local_episode = $this->em->getRepository('OktolabMediaBundle:Episode')->findOneBy(array('uniqID' => $uniqID));
            if (!$local_episode) {
                $local_episode = new Episode();
            }
            $local_episode->merge($episode);
            $local_episode->setVideo($this->importAsset($keychain, $episode->getVideo()));
            $local_episode->setPosterframe($this->importAsset($keychain, $episode->getPosterframe()));
            $this->em->persist($episode);
            if ($flush) {
                $this->em->flush();
                $this->em->clear();
            }
        } else {
            //something went wrong. Application not responding correctly
        }

    }

    /**
    * imports and returns asset
    */
    private function importAsset(Keychain $keychain, $remote_asset, $adapter = null)
    {
        $key = $remote_asset->getKey();
        $asset = new Asset();
        $asset->setKey($key);
        if (!$adapter) {
            $adapter = $remote_asset->getAdapter();
        }
        $asset->setAdapter($adapter);
        $asset->setName($remote_asset->getName());
        $asset->setMimetype($remote_asset->getMimetype());
        shell_exec(
            sprintf('wget --http-user=%s --http-password=%s %s --output-document=%s',
                $keychain->getUser(),
                $keychain->getApiKey(),
                $keychain->getUrl().'/api/oktolab_media/asset'.$key,
                $this->adapters[$adapter]['path'].'/'.$key
            )
        );
        $this->em->persist($asset);
        return $asset;
    }

    public function importSeries(Keychain $keychain, $uniqID, $andEpisodes = false)
    {
        $response = $this->guzzle->post(
            $keychain->getUrl().'/api/oktolab_media/series/'.$uniqID,
            ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
        );
        if ($response->getStatusCode() == 200) {
            $series = $this->serializer->deserialize($response->getBody(), $this->series_class, 'json');
            $local_series = $this->em->getRepository('OktolabMediaBundle:Series')->findOneBy(array('uniqID' => $uniqID));
            if (!$local_series) {
                $local_series = new Series();
            }
            $local_series->merge($series);
            $local_series->setPosterframe($this->importAsset($keychain, $series->getPosterframe()));
            if ($andEpisodes) {
                gc_enable();
                foreach ($series->getEpisodes() as $episode) {
                    $this->importEpisode($keychain, $episode->getUniqID());
                    unset($episode);
                }
                gc_disable();
            }
            $this->delorian_em->persist($local_series);
            $this->delorian_em->flush();
        } else {
            //something went wrong. Application not responding correctly
        }
    }
}
