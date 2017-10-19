<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Episode;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\FinalizedEpisodeEvent;

/**
 * checks if all media of an episode respond with 200 and sets the active flag accordingly
 */
class FinalizeVideoJob extends BprsContainerAwareJob
{
    private $em;
    private $media_service;
    private $asset_helper_service;
    private $logbook;

    public function getName() {
        return 'Finalize Episode';
    }

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info('oktolab_media.episode_start_finalize', [], $this->args['uniqID']);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->media_service = $this->getContainer()->get('oktolab_media');
        $episode = $this->media_service->getEpisode($this->args['uniqID']);
        if ($episode) {
            $this->asset_helper_service = $this->getContainer()->get('bprs.asset_helper');

            $this->media_service->setEpisodeStatus(
                $this->args['uniqID'],
                Episode::STATE_FINALIZING
            );

            if ($this->checkMediaStatus($episode)) {
                $episode->setIsActive(true);
                $episode->setTechnicalStatus(Episode::STATE_READY);
                $this->em->persist($episode);
                $this->em->flush();
                $this->media_service->dispatchFinalizedEpisodeEvent($this->args);
            } else {
                $episode->setIsActive(false);
                $episode->setTechnicalStatus(Episode::STATE_FINALIZING_FAILED);
                $this->em->persist($episode);
                $this->em->flush();
            }

        } else { // episode not found
            $this->logbook->error('oktolab_media.episode_finalize_error', [], $this->args['uniqID']);
        }
        $this->logbook->info('oktolab_media.episode_end_finalize', [], $this->args['uniqID']);
    }

    private function checkMediaStatus($episode)
    {
        $is_active = true;

        foreach ($episode->getMedia() as $media) {
            if ($media->isActive()) {
                if ($media->getAsset() !== null && $media->getProgress() >= 100) {
                    $url = $this->asset_helper_service->getAbsoluteUrl($media->getAsset());
                    $this->logbook->info('oktolab_media.episode_finalize_url', ['%media%' => $media->getQuality(),'%url%' => $url], $this->args['uniqID']);

                    $client = new Client();
                    $response = $client->request('GET', $url);
                    if ($response->getStatusCode() != Response::HTTP_OK) {
                        $this->logbook->warning('oktolab_media.episode_finalize_not_ok', ['%media%' => $media->getQuality(),'%url%' => $url], $this->args['uniqID']);
                        $is_active = false;
                    }
                } else {
                    $is_active = false;
                }
            }
        }

        // if episode medias are 0, set is_active to false.
        if (!count($episode->getMedia())) {
            $is_active = false;
        }

        return $is_active;
    }
}
