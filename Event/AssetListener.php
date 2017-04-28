<?php

namespace Oktolab\MediaBundle\Event;

use Bprs\AssetBundle\Event\DeleteAssetEvent;

class AssetListener
{
    private $em;
    private $episode_class;
    private $series_class;
    private $media_class;

    public function __construct($entity_manager, $episode_class, $series_class, $media_class)
    {
        $this->em = $entity_manager;
        $this->episode_class = $episode_class;
        $this->series_class = $series_class;
        $this->media_class = $media_class;
    }

    public function onAssetDelete(DeleteAssetEvent $event)
    {
        // The cascading for media, episode and series asset removal collides with the
        // doctrine cascading. You can't delete a series object graph with flushes inbetween.
        // wait for an update in the bprs asset bundle for a specific deleteMaybeUsedAsset Event.

        // $this->updateMedias($event->getAsset());
        // $this->updateEpisodes($event->getAsset());
        // $this->updateSeries($event->getAsset());
    }

    public function updateMedias($asset)
    {
        $medias = $this->em->createQuery(
                'SELECT m FROM '.$this->media_class.' m WHERE m.asset = :id'
            )
            ->setParameter('id', $asset->getId())
            ->getResult();

            foreach ($medias as $media) {
                $media->setAsset(null);
                $this->em->remove($media);
            }
    }

    private function updateEpisodes($asset)
    {
        $episodes = $this->em->createQuery(
                'SELECT e FROM '.$this->episode_class.' e WHERE e.posterframe = :id OR e.video = :id'
            )
            ->setParameter('id', $asset->getId())
            ->getResult();

            foreach ($episodes as $episode) {
                if ($episode->getPosterframe() != null && $episode->getPosterframe()->getFilekey() == $asset->getFilekey()) {
                    $episode->setPosterframe(Null);
                } elseif ($episode->getVideo() != null && $episode->getVideo()->getFilekey() == $asset->getFilekey()) {
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
