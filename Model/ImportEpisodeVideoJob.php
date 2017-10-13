<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

/**
 * 1: Imports the video by filekey and keychain
 * 2: Triggers EncodeVideoJob if import was successful
 */
class ImportEpisodeVideoJob extends BprsContainerAwareJob
{
    public function perform() {
        $mediaService = $this->getContainer()->get('oktolab_media');
        $episode = $mediaService->getEpisode($this->args['uniqID']);
        $keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        $logbook = $this->getContainer()->get('bprs_logbook');
        $asset_service = $this->getContainer()->get('bprs.asset');
        $cacheFS = $this->getContainer()->getParameter('oktolab_media.encoding_filesystem');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($keychain && $episode) {
            $logbook->info('oktolab_media.start_import_episode_video', [], $episode->getUniqID());

            $response = $response = $mediaService->getResponse($keychain, MediaService::ROUTE_ASSET, ['filekey' => $this->args['key']]);
            if ($response->getStatusCode() == 200) {
                $remote_asset = json_decode($response->getBody());
                $asset = $asset_service->createAsset();
                // $asset->setFilekey($this->args['key']);
                $asset->setAdapter($cacheFS);
                $asset->setName($remote_asset->name);
                $asset->setMimetype($remote_asset->mimetype);

                $mediaHelper = $this->getContainer()->get('oktolab_media_helper');

                $applinkservice = $this->getContainer()->get('bprs_applink');

                shell_exec(
                    sprintf('wget --http-user=%s --http-password=%s "%s" --output-document="%s"',
                        $keychain->getUser(),
                        $keychain->getApiKey(),
                        $applinkservice->getApiUrlsForKey($keychain, 'bprs_asset_api_download').'?'.http_build_query(['filekey' => $this->args['key']]),
                        $mediaHelper->getAdapters()[$cacheFS]['path'].'/'.$asset->getFilekey()
                    )
                );

                // delete old videofile if one exists
                $this->getContainer()->get('oktolab_media_helper')->deleteVideo($episode);

                $episode->setVideo($asset);
                $em->persist($episode);
                $em->persist($asset);
                $em->flush();

                //trigger episode encoding
                $mediaService->addEncodeEpisodeJob($episode->getUniqID());
            }
            $logbook->info('oktolab_media.end_import_episode_video', [], $episode->getUniqID());
        }
    }

    public function getName()
    {
        return 'Import Episode Video';
    }
}
