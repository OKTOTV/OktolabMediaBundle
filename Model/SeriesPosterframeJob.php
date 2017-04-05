<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Model\MediaService;

/**
 * this job imports a posterframe for a given series (uniqID) from
 * a keychain (keychain), creates and stores a new asset at the cache system
 * and adds a move asset job to the posterframe filesystem. If the import was
 * successful, a imported_series_posterframe event will be fired.
 */
class SeriesPosterframeJob extends BprsContainerAwareJob {
    private $logbook;
    private $media_service;
    private $media_helper_service;
    private $series;

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->media_service = $this->getContainer()->get('oktolab_media');
        $this->media_helper_service = $this->getContainer()->get('oktolab_media_helper');

        $this->series = $this->media_service->getSeries($this->args['uniqID']);

        if ($this->series) {
            $keychain = array_key_exists('keychain', $this->args) ? $this->args['keychain'] : $this->series->getKeychain();
            if ($keychain) {
                $this->logbook->info(
                    'oktolab_media.start_import_series_posterframe',
                    [],
                    $this->args['uniqID']
                );
                $remote_series = $this->getContainer()->get('oktolab_keychain')->getSeries($keychain, $this->args['uniqID']);
                if ($remote_series) {
                    if ($this->importAsset($keychain, $remote_series->getPosterframe())) {
                        $this->media_service->dispatchImportedSeriesPosterframeEvent($this->args);
                    } else { // no remote asset
                        $this->logbook->info(
                            'oktolab_media.import_series_posterframe_no_remote_asset',
                            [],
                            $this->args['uniqID']
                        );
                    }
                } else { // remote series not found
                    $this->logbook->info(
                        'oktolab_media.import_series_posterframe_no_series_found',
                        [],
                        $this->args['uniqID']
                    );
                }
            } else { // no keychain
                $this->logbook->info(
                    'oktolab_media.import_series_posterframe_no_keychain_found',
                    [],
                    $this->args['uniqID']
                );
            }
            $this->logbook->info(
                'oktolab_media.end_import_series_posterframe',
                [],
                $this->args['uniqID']
            );
        } else { // no local series
            $this->logbook->info(
                'oktolab_media.import_series_posterframe_no_local_series',
                [],
                $this->args['uniqID']
            );
        }
    }

    private function importAsset($keychain, $filekey) {
        $remote_asset = $this->getContainer()->get('bprs.asset_keychain')->getAsset($keychain, $filekey);
        if ($remote_asset) {
            $asset = $this->getContainer()->get('bprs.asset')->createAsset();
            $asset->setAdapter($this->getContainer()->getParameter('oktolab_media.encoding_filesystem'));
            $asset->setName($remote_asset->getName());
            $asset->setMimetype($remote_asset->getMimetype());

            // download data
            $this->downloadFile($keychain, $filekey, $asset);

            // remove old posterframe
            $this->media_helper_service->deleteSeriesPosterframe($this->series);

            // set new posterframe
            $this->series->setPosterframe($asset);
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $em->persist($this->series);
            $em->persist($asset);
            $em->flush();

            //move to correct destination
            $this->getContainer()->get('bprs.asset_job')->addMoveAssetJob(
                $asset,
                $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem')
            );

            return true;
        } else { // remote series has no posterframe
            return false;
        }
    }

    /**
     * @var keychain of remote app to download file
     * @var filekey of the remote posterframe
     * @var asset local prepared new asset to link to the new file
     */
    private function downloadFile($keychain, $filekey, $asset) {
        $applinkservice = $this->getContainer()->get('bprs_applink');

        $url = sprintf(
            "%s?%s",
            $applinkservice->getApiUrlsForKey($keychain, 'bprs_asset_api_download'),
            http_build_query(['filekey' => $filekey])
        );
        $path = sprintf(
            "%s/%s",
            $this->media_helper_service->getAdapters()[$asset->getAdapter()]['path'],
            $asset->getFilekey()
        );

        shell_exec(
            sprintf('wget --http-user=%s --http-password=%s "%s" --output-document="%s"',
                $keychain->getUser(),
                $keychain->getApiKey(),
                $url,
                $path
            )
        );
    }

    public function getName() {
        return 'Import Series Posterframe';
    }
}
