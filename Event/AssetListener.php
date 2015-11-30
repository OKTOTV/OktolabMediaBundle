<?php

namespace Oktolab\MediaBundle\Event;

use Bprs\AssetBundle\Event\DeleteAssetEvent;

class AssetListener
{
    private $em;
    private $episode_class;
    private $series_class;

    public function __construct($entity_manager, $episode_class, $series_class)
    {
        $this->em = $entity_manager;
        $this->episode_class = $episode_class;
        $this->series_class = $series_class;
    }

    public function onAssetDelete(DeleteAssetEvent $event)
    {
        $this->updateEpisodes($event->getAsset());
        $this->updateSeries($event->getAsset());
    }

    private function updateEpisodes($asset)
    {
        $episodes = $this->em->createQuery(
                'SELECT e FROM '.$this->episode_class.' e WHERE e.posterframe = :id OR e.video = :id'
            )
            ->setParameter('id', $asset->getId())
            ->getResult();

        foreach ($episodes as $episode) {
            if ($episode->getPosterframe()->getFilekey() == $asset->getFilekey()) {
                $episode->setPosterframe(Null);
            } elseif ($episode->getVideo()->getFilekey() == $asset->getFilekey()) {
                $episode->setVideo(Null);
            }
            $this->em->persist($episode);
        }
    }

    private function updateSeries($asset)
    {
        $seriess = $this->em->createQuery(
                'SELECT s FROM '.$this->series_class.' s WHERE s.posterframe = :id'
            )
            ->setParameter('id', $asset->getId())
            ->getResult();

        foreach ($seriess as $series) {
            if ($series->getPosterframe()->getFilekey() == $asset->getFilekey()) {
                $series->setPosterframe(Null);
                $this->em->persist($series);
            }
        }
        $this->em->flush();
    }
}
 ?>
