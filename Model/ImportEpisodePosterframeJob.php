<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportEpisodePosterframeJob extends BprsContainerAwareJob
{
    public function perform() {
        $mediaService = $this->getContainer()->get('oktolab_media');
        $episode = $mediaService->getEpisode($this->args['uniqID']);
        $keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        $logbook = $this->getContainer()->get('bprs_logbook');
        $asset_service = $this->getContainer()->get('bprs_asset');
        $cacheFS = $this->getContainer()->getParameter('oktolab_media.encoding_filesystem');
        $posterframeFS = $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($keychain && $episode) {
            $logbook->info('oktolab_media.start_import_episode_posterframe', [], $episode->getUniqID());

            $response = $response = $this->mediaService->getResponse($this->keychain, MediaService::ROUTE_ASSET, ['key' => $this->args['key']]);
            if ($response->getStatusCode() == 200) {
                $remote_asset = json_decode($response->getBody());
                $asset = $asset_service->createAsset();
                $asset->setFilekey($this->args['key']);
                $asset->setAdapter($cacheFS);
                $asset->setName($remote_asset->name);
                $asset->setMimetype($remote_asset->mimetype);

                $mediaHelper = $this->getContainer()->get('oktolab_media_helper');

                $applinkservice = $this->getContainer()->get('bprs_applink');

                shell_exec(
                    sprintf('wget --http-user=%s --http-password=%s "%s" --output-document="%s"',
                        $keychain->getUser(),
                        $keychain->getApiKey(),
                        $applinkservice->getApiUrlsForKey($keychain, 'bprs_asset_api_download').'?'.http_build_query(['key' => $asset->getFilekey()]),
                        $mediaHelper->getAdapters()[$cacheFS]['path'].'/'.$key
                    )
                );
                //remove any previous attached posterframes
                if ($episode->getPosterframe()) {
                    $asset_service->getHelper()->deleteAsset($episode->getPosterframe());
                }
                $episode->setPosterframe($asset);
                $em->persist($episode);
                $em->persist($asset);
                $em->flush();
                //move to posterframeFS to correct destination
                $this->getContainer()->get('asset_jobservice')->addMoveAssetJob($asset, $posterframeFS);
            }
            $logbook->info('oktolab_media.end_import_episode_posterframe', [], $episode->getUniqID());
        }
    }

    public function getName()
    {
        return 'Import Episode Posterframe';
    }
}
?>
