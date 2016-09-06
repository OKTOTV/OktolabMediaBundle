<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Model\MediaService;

class ImportEpisodeJob extends BprsContainerAwareJob
{
    private $mediaService;
    private $serializer;
    private $logbook;
    private $keychain;
    private $em;
    private $defaultFS;
    private $posterframeFS;

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info('oktolab_media.episode_metadata_start_import', [], $this->args['uniqID']);
        $this->keychain = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('BprsAppLinkBundle:Keychain')->findOneBy(array('user' => $this->args['user']));
        if ($this->keychain) {
            $this->mediaService = $this->getContainer()->get('oktolab_media');
            $this->serializer =   $this->getContainer()->get('jms_serializer');
            $episode_class = $this->getContainer()->getParameter('oktolab_media.episode_class');
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $this->defaultFS = $this->getContainer()->getParameter('oktolab_media.default_filesystem');
            $this->posterframeFS = $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem');

            $response = $this->mediaService->getResponse($this->keychain, MediaService::ROUTE_EPISODE, ['uniqID' => $this->args['uniqID']]);
            if ($response->getStatusCode() == 200) {
                $episode = $this->serializer->deserialize($response->getBody(), $episode_class, 'json');
                $local_episode = $this->mediaService->getEpisode($uniqID);
                $local_series = $this->mediaService->getSeries($episode->getSeries()->getUniqID());
                if (!$local_series) {
                    $local_series = $this->importSeries($episode->getSeries()->getUniqID());
                }
                if (!$local_episode) {
                    $local_episode = new $this->episode_class;
                }
                $local_episode->merge($episode);
                $local_episode->setSeries($local_series);
                $local_series->addEpisode($local_episode);

                $this->mediaService->addImportEpisodeVideoJob($this->args['uniqID'], $keychain, $episode->getVideo());
                $this->mediaService->addImportEpisodePosterframeJob($this->args['uniqID'], $keychain, $episode->getPosterframe());

                $this->em->persist($local_episode);
                $this->em->persist($local_series);

                if ($flush) {
                    $this->em->flush();
                }
            } else {
                $this->mediaService->setEpisodeStatus($uniqID, Episode::STATE_NOT_READY);
                $this->logbook->error('oktolab_media.episode_metadata_error_end_import', [], $this->args['uniqID']);
            }
            $this->logbook->info('oktolab_media.episode_metadata_end_import', [], $this->args['uniqID']);
        } else{
            $this->logbook->warning('oktolab_media.episode_metadata_import_no_keychain', [], $this->args['uniqID']);
        }
    }

    public function getName()
    {
        return 'Import Episode Metadata';
    }

    private function importSeries($uniqID)
    {
        $response = $this->mediaService->getResponse(
            $this->keychain,
            mediaService::ROUTE_SERIES,
            ['uniqID' => $uniqID]
        );
        if ($response->getStatusCode() == 200) {
            $series = $this->serializer->deserialize($response->getBody(), $this->series_class, 'json');
            $local_series = $this->mediaService->getSeries($uniqID);
            if (!$local_series) {
                $series_class = $this->getContainer()->getParameter('oktolab_media.series_class');
                $local_series = new $series_class;
            }
            $local_series->merge($series);

            //import Series Posterframe
            $this->mediaService->addImportSeriesPosterframeJob($local_series->getUniqID(), $keychain, $series->getPosterframe());
            return $series;
        }
        return null;
    }
?>
