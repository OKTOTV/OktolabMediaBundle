<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Model\MediaService;
use Oktolab\MediaBundle\Entity\Episode;

class ImportEpisodeMetadataJob extends BprsContainerAwareJob
{
    private $mediaService;
    private $serializer;
    private $logbook;
    private $keychain;

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info(
            'oktolab_media.episode_metadata_start_import',
            [],
            $this->args['uniqID']
        );
        $this->keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        if ($this->keychain) {
            $this->mediaService = $this->getContainer()->get('oktolab_media');
            $local_episode = $this->mediaService->getEpisode($this->args['uniqID']);
            if (filter_var($this->args['overwrite'], FILTER_VALIDATE_BOOLEAN) || $local_episode == null) {
            // if the episode can be overwritten or the episode doesn't exist yet
                $this->serializer = $this->getContainer()->get('jms_serializer');
                $episode_class = $this->getContainer()->getParameter('oktolab_media.episode_class');

                $response = $this->mediaService->getResponse(
                    $this->keychain, MediaService::ROUTE_EPISODE,
                    ['uniqID' => $this->args['uniqID']]
                );
                if ($response->getStatusCode() == 200) {
                    $episode = $this->serializer->deserialize(
                        $response->getBody(),
                        $episode_class,
                        'json'
                    );

                    if (!$local_episode) {
                        $local_episode = $this->mediaService->createEpisode();
                    }
                    $local_episode->merge($episode);
                    $local_episode->setKeychain($this->keychain);

                    $this->mediaService->addImportEpisodePosterframeJob(
                        $this->args['uniqID'],
                        $this->keychain,
                        $episode->getPosterframe()
                    );
                    $this->mediaService->addImportEpisodeVideoJob(
                        $this->args['uniqID'],
                        $this->keychain,
                        $episode->getVideo()
                    );

                    $em = $this->getContainer()->get('doctrine.orm.entity_manager');
                    $em->persist($local_episode);
                    $em->flush();

                } else {
                    $this->mediaService->setEpisodeStatus(
                        $uniqID,
                        Episode::STATE_NOT_READY
                    );

                    $this->logbook->error(
                        'oktolab_media.episode_metadata_error_end_import',
                        [],
                        $this->args['uniqID']
                    );
                }
            } else { // no overwrite and episode exists
                $this->logbook->info(
                    'oktolab_media.episode_overwrite_not_allowed',
                    [],
                    $this->args['uniqID']
                );
            }
            $this->logbook->info(
                'oktolab_media.episode_metadata_end_import',
                [],
                $this->args['uniqID']
            );
            $this->mediaService
                ->dispatchImportedEpisodeMetadataEvent($this->args);
        } else{ // no keychain found
            $this->logbook->warning(
                'oktolab_media.episode_metadata_import_no_keychain',
                [],
                $this->args['uniqID']
            );
        }
    }

    public function getName()
    {
        return 'Import Episode Metadata';
    }
}
