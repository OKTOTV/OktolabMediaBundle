<?php

namespace Oktolab\MediaBundle\Model;

use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\DeleteSeriesEvent;
use Oktolab\MediaBundle\Event\DeleteEpisodeEvent;

class MediaHelperService {

    private $em;
    private $asset_service;
    private $logbook;
    private $dispatcher;
    private $adapters;

    public function __construct($em, $asset_service, $logbook, $dispatcher, $adapters)
    {
        $this->em = $em;
        $this->asset_service = $asset_service;
        $this->logbook = $logbook;
        $this->dispatcher = $dispatcher;
        $this->adapters = $adapters;
    }

    public function persistEpisode($episode, $and_flush = true)
    {
        // TODO: dispatch event
        $this->em->persist($episode);
        if ($and_flush) {
            $this->em->flush();
        }
    }

    public function persistSeries($series, $and_flush = true)
    {
        // TODO: dispatch event
        $this->em->persist($series);
        if ($and_flush) {
            $this->em->flush();
        }
    }

    public function deleteEpisode($episode)
    {
        $this->logbook->info('oktolab_media.logbook_delete_episode_start', [], $episode->getUniqID());

        $event = new DeleteEpisodeEvent($episode);
        $this->dispatcher->dispatch(OktolabMediaEvent::DELETE_EPISODE, $event);

        $this->em->remove($episode);
        $this->deleteMedia($episode, true);
        if ($episode->getPosterframe()) {
            $this->deletePosterframe($episode);
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_episode_end', [], $episode->getUniqID());
    }

    public function deleteSeries($series)
    {
        $this->logbook->info('oktolab_media.logbook_delete_series_start', [], $series->getUniqID());

        $event = new DeleteSeriesEvent($series);
        $this->dispatcher->dispatch(OktolabMediaEvent::DELETE_SERIES, $event);

        $this->em->remove($series);
        if ($series->getPosterframe()) {
            $this->asset_service->deleteAsset($series->getPosterframe());
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_series_end', [], $series->getUniqID());
    }

    public function deleteMedia($episode, $including_video = false)
    {
        $this->logbook->info('oktolab_media.logbook_delete_media_start', [], $episode->getUniqID());
        foreach ($episode->getMedia() as $media) {
            $this->logbook->info(
                'oktolab_media.logbook_delete_media',
                ['%media%', $media->getQuality()],
                $episode->getUniqID()
            );

            // delete all media.
            if ($media->getAsset() == $episode->getVideo()) {
                $media->setAsset(null);
            }
            $this->em->remove($media);

        }
        $this->em->flush();

        if ($including_video) {
            $this->deleteVideo($episode);
        }

        $this->logbook->info('oktolab_media.logbook_delete_media_end', [], $episode->getUniqID());
    }

    public function deleteVideo($episode, $including_media = false)
    {
        $this->logbook->info('oktolab_media.logbook_delete_video_start', [], $episode->getUniqID());
        if ($episode->getVideo()) {
            $video = $episode->getVideo();
            $can_delete_video = true;
            foreach($episode->getMedia() as $media) {
                if ($media->getAsset() == $video) {
                    $can_delete_video = false;
                }
            }
            $episode->setVideo(null);
            if ($can_delete_video) {
                $this->asset_service->deleteAsset($video);
            }
            if ($including_media) {
                $this->deleteMedia($episode);
            }
        }
        $this->logbook->info('oktolab_media.logbook_delete_video_end', [], $episode->getUniqID());
    }

    /**
     * @deprecated
     */
    public function deletePosterframe($episode)
    {
        $this->deleteEpisodePosterframe($episode);
    }

    public function deleteEpisodePosterframe($episode)
    {
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_start', [], $episode->getUniqID());
        if ($episode->getPosterframe()) {
            $this->asset_service->deleteAsset($episode->getPosterframe());
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_end', [], $episode->getUniqID());
    }

    public function deleteSeriesPosterframe($series)
    {
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_start', [], $series->getUniqID());
        if ($series->getPosterframe()) {
            $this->asset_service->deleteAsset($series->getPosterframe());
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_end', [], $series->getUniqID());
    }

    public function getAdapters()
    {
        return $this->adapters;
    }
}

 ?>
