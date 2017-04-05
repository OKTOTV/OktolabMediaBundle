<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class EpisodePosterframeJob extends BprsContainerAwareJob
{
    private $logbook;
    private $media_service;
    private $media_helper_service;
    private $asset_service;
    private $episode;
    private $keychain;

    public function perform() {
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->media_service = $this->getContainer()->get('oktolab_media');
        $this->media_helper_service = $this->getContainer()->get('oktolab_media_helper');
        $this->episode = $this->media_service->getEpisode($this->args['uniqID']);

        if ($this->episode) {
            $this->keychain = array_key_exists('keychain', $this->args) ? $this->args['keychain'] : $this->episode->getKeychain();
            if ($this->keychain) {
                $this->logbook->info(
                    'oktolab_media.start_import_episode_posterframe',
                    [],
                    $this->episode->getUniqID()
                );
                $remote_episode = $this->getContainer()->get('oktolab_keychain')->getEpisode(
                    $this->keychain,
                    $this->args['uniqID']
                );
                if ($remote_episode) {
                    if ($this->importAsset($this->keychain, $remote_episode->getPosterframe())) {
                        $this->media_service->dispatchImportedEpisodePosterframeEvent($this->args);
                    } else { // no remote asset

                    }
                } else { // no remote episode

                }
            } else { // no keychain

            }
        } else { // no local episode

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
            $this->media_helper_service->deleteEpisodePosterframe($this->episode);

            // set new posterframe
            $this->episode->setPosterframe($asset);
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $em->persist($this->episode);
            $em->persist($asset);
            $em->flush();

            //move to correct destination
            $this->getContainer()->get('bprs.asset_job')->addMoveAssetJob(
                $asset,
                $this->getContainer()->getParameter('oktolab_media.posterframe_filesystem')
            );

            return true;
        } else { // remote episode has no posterframe
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

    public function getName()
    {
        return 'Import Episode Posterframe';
    }
}
