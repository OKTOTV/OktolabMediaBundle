<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportSeriesJob extends BprsContainerAwareJob
{
    public function perform() {
        $this->getContainer()->get('bprs_logbook')->info('oktolab_media.series_start_import', [], $this->args['uniqID']);
        $keychain = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('BprsAppLinkBundle:Keychain')->findOneBy(array('user' => $this->args['user']));
        if ($keychain) {
            $oktolabMediaService = $this->getContainer()->get('oktolab_media');
            $oktolabMediaService->importSeries($keychain, $this->args['uniqID']);
            $this->getContainer()->get('bprs_logbook')->info('oktolab_media.series_end_import', [], $this->args['uniqID']);
        } else {
            $this->getContainer()->get('bprs_logbook')->warning('oktolab_media.series_import_no_keychain', [], $this->args['uniqID']);
        }
    }

    public function getName()
    {
        return 'Import Series';
    }
}
?>
