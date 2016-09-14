<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Model\MediaService;

class ImportEpisodeMetadataJob extends BprsContainerAwareJob
{
    private $mediaService;
    private $serializer;
    private $logbook;
    private $keychain;
    private $defaultFS;
    private $posterframeFS;

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info('oktolab_media.episode_metadata_start_import', [], $this->args['uniqID']);
        $this->keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        if ($this->keychain) {
            $this->mediaService = $this->getContainer()->get('oktolab_media');
            $this->serializer = $this->getContainer()->get('jms_serializer');
            $episode_class = $this->getContainer()->getParameter('oktolab_media.episode_class');
            $this->defaultFS = $this->getContainer()->getParameter('oktolab_media.default_filesystem');
            $this->posterframeFS = $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem');

            $response = $this->mediaService->getResponse($this->keychain, MediaService::ROUTE_EPISODE, ['uniqID' => $this->args['uniqID']]);
            if ($response->getStatusCode() == 200) {
                $episode = $this->serializer->deserialize($response->getBody(), $episode_class, 'json');
                $local_episode = $this->mediaService->getEpisode($this->args['uniqID']);
                $local_episode->merge($episode);
                $local_episode->setKeychain($this->keychain);

                $this->mediaService->addImportEpisodePosterframeJob($this->args['uniqID'], $this->keychain, $episode->getPosterframe());
                $this->mediaService->addImportEpisodeVideoJob($this->args['uniqID'], $this->keychain, $episode->getVideo());

                $em = $this->getContainer()->get('doctrine.orm.entity_manager');
                $em->persist($local_episode);
                $em->flush();

            } else {
                $this->mediaService->setEpisodeStatus($uniqID, Episode::STATE_NOT_READY);
                $this->logbook->error('oktolab_media.episode_metadata_error_end_import', [], $this->args['uniqID']);
            }
            $this->logbook->info('oktolab_media.episode_metadata_end_import', [], $this->args['uniqID']);
            $this->mediaService->dispatchImportedEpisodeMetadataEvent($this->args['uniqID']);
        } else{
            $this->logbook->warning('oktolab_media.episode_metadata_import_no_keychain', [], $this->args['uniqID']);
        }
    }

    public function getName()
    {
        return 'Import Episode Metadata';
    }
}
