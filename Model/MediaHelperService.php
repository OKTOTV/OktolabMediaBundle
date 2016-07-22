<?php

namespace Oktolab\MediaBundle\Model;

use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\DeleteSeriesEvent;

class MediaHelperService {

    private $em;
    private $media_helper;
    private $logbook;
    private $dispatcher;

    public function __construct($em, $media_helper, $logbook, $dispatcher)
    {
        $this->em = $em;
        $this->media_helper = $media_helper;
        $this->logbook = $logbook;
        $this->dispatcher = $dispatcher;
    }

    public function deleteEpisode($episode)
    {
        $this->logbook->info('oktolab_media.logbook_delete_episode_start', [], $episode->getUniqID());
        $this->em->remove($episode);
        $this->deleteMedia($episode, true);
        if ($episode->getPosterframe()) {
            $this->media_helper->deleteAsset($episode->getPosterframe());
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
            $this->media_helper->deleteAsset($series->getPosterframe());
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
                    $this->media_helper->deleteAsset($media->getAsset());
                }
            } else { // delete media
                $this->em->remove($media);
                $this->media_helper->deleteAsset($media->getAsset());

            }
        }
        $this->em->flush();
        $this->logbook->info('oktolab_media.logbook_delete_media_end', [], $episode->getUniqID());
    }
}

 ?>
