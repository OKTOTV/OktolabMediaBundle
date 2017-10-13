<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportEpisodeFileFromUrlJob extends BprsContainerAwareJob
{
    private $client;
    private $logbook;
    private $mediaService;
    private $asset_service;
    private $em;

    public function perform()
    {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info(
            'oktolab_media.episode_file_from_url_start_import',
            ['%url%' => $this->args['url']],
            $this->args['uniqID']
        );
        $this->mediaService = $this->getContainer()->get('oktolab_media');
        $episode = $this->mediaService->getEpisode($this->args['uniqID']);

        if ($episode) {
            $this->asset_service = $this->getContainer()->get('bprs.asset');
            $mediaHelper = $this->getContainer()->get('oktolab_media_helper');
            $cacheFS = $this->getContainer()->getParameter('oktolab_media.encoding_filesystem');
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $asset = $this->asset_service->createAsset();
            $asset->setAdapter($cacheFS);
            $asset->setName($episode->getName());
            // $asset->setMimetype($remote_asset->mimetype);

            $command = null;
            if (strpos($this->args['url'], 'youtube.com') !== false) { // use youtube-dl to download file
                $command = sprintf('youtube-dl "%s" -o "%s"',
                    $this->args['url'],
                    $mediaHelper->getAdapters()[$cacheFS]['path'].'/'.$asset->getFilekey()
                );
            } else {
                $command = sprintf('wget "%s" --output-document="%s"',
                    $this->args['url'],
                    $mediaHelper->getAdapters()[$cacheFS]['path'].'/'.$asset->getFilekey()
                );
            }
            $this->logbook->info(
                'oktolab_media.episode_file_from_url_command',
                ["%command%" => $command],
                $this->args['uniqID']
            );
            shell_exec($command);

            // delete old videofile if one exists
            $mediaHelper->deleteVideo($episode);

            $episode->setVideo($asset);
            $this->em->persist($episode);
            $this->em->persist($asset);
            $this->em->flush();

            //trigger episode encoding
            $this->mediaService->addEncodeEpisodeJob($episode->getUniqID());
        } else { // no episode found!
            $this->logbook->info(
                'oktolab_media.episode_file_from_url_no_episode',
                [],
                $this->args['uniqID']
            );
        }

    }
}
