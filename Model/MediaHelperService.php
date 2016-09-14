<?php

namespace Oktolab\MediaBundle\Model;

use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\DeleteSeriesEvent;

class MediaHelperService {

    private $em;
    private $asset_helper;
    private $logbook;
    private $dispatcher;
    private $adapters;

    public function __construct($em, $asset_helper, $logbook, $dispatcher, $adapters)
    {
        $this->em = $em;
        $this->asset_helper = $asset_helper;
        $this->logbook = $logbook;
        $this->dispatcher = $dispatcher;
        $this->adapters = $adapters;
    }

    public function deleteEpisode($episode)
    {
        $this->logbook->info('oktolab_media.logbook_delete_episode_start', [], $episode->getUniqID());
        $this->em->remove($episode);
        $this->deleteMedia($episode, true);
        if ($episode->getPosterframe()) {
            $this->asset_helper->deleteAsset($episode->getPosterframe());
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
            $this->asset_helper->deleteAsset($series->getPosterframe());
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_series_end', [], $series->getUniqID());
    }

    public function deleteMedia($episode, $including_video = false)
    {
        $this->logbook->info('oktolab_media.logbook_delete_media_start', [], $episode->getUniqID());
        foreach ($episode->getMedia() as $media) {
            $this->logbook->info('oktolab_media.logbook_delete_media', ['%media%', $media->getQuality()], $episode->getUniqID());
            // keep the linked video asset of the media
            if ($media == $episode->getVideo()) {
                $this->em->remove($media);
                if ($including_video) {
                    $this->asset_helper->deleteAsset($media->getAsset());
                }
            } else { // delete media
                $this->em->remove($media);
                $this->asset_helper->deleteAsset($media->getAsset());

            }
        }
        $this->em->flush();
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
                $this->asset_helper->deleteAsset($video);
            }
            if ($including_media) {
                $this->deleteMedia($episode);
            }
        }
        $this->logbook->info('oktolab_media.logbook_delete_video_end', [], $episode->getUniqID());
    }

    public function deletePosterframe($episode)
    {
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_start', [], $episode->getUniqID());
        if ($episode->getPosterframe()) {
            $this->em->remove($episode->getPosterframe());
            $this->asset_helper->deleteAsset($episode->getPosterframe());
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_posterframe_end', [], $episode->getUniqID());
    }

    public function getAdapters()
    {
        return $this->adapters;
    }
}

 ?>
