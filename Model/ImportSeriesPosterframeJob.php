<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportSeriesPosterframeJob extends BprsContainerAwareJob
{
    public function perform() {
        $mediaService = $this->getContainer()->get('oktolab_media');
        $series = $mediaService->getSeries($this->args['uniqID']);
        $keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        $logbook = $this->getContainer()->get('bprs_logbook');
        $asset_service = $this->getContainer()->get('bprs.asset');
        $cacheFS = $this->getContainer()->getParameter('oktolab_media.encoding_filesystem');
        $posterframeFS = $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        if ($keychain && $series) {
            $logbook->info('oktolab_media.start_import_series_posterframe', [], $this->args['uniqID']);

            $response = $response = $mediaService->getResponse($keychain, MediaService::ROUTE_ASSET, ['filekey' => $this->args['key']]);
            if ($response->getStatusCode() == 200) {
                $remote_asset = json_decode($response->getBody());
                $asset = $asset_service->createAsset();
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
                        $mediaHelper->getAdapters()[$cacheFS]['path'].'/'.$key
                    )
                );
                //remove any previous attached posterframes
                if ($series->getPosterframe()) {
                    $asset_service->getHelper()->deleteAsset($series->getPosterframe());
                }
                $series->setPosterframe($asset);
                $em->persist($series);
                $em->persist($asset);
                $em->flush();
                //move to posterframeFS to correct destination
                $this->getContainer()->get('asset_jobservice')->addMoveAssetJob($asset, $posterframeFS);
            }
            $logbook->info('oktolab_media.end_import_series_posterframe', [], $this->args['uniqID']);
        }
    }

    public function getName()
    {
        return 'Import Series Posterframe';
    }
}
