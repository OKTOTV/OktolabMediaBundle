<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportSeriesJob extends BprsContainerAwareJob
{
    public function perform() {
        echo "Start series import\n";
        $keychain = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('BprsAppLinkBundle:Keychain')->findOneBy(array('user' => $this->args['user']));
        if ($keychain) {
            $oktolabMediaService = $this->getContainer()->get('oktolab_media');
            $oktolabMediaService->importSeries($keychain, $this->args['uniqID']);
            echo "End of series import\n";
        } else {
            echo "No keychain found! Abort action\n";
        }
    }

    public function getName()
    {
        return 'Import Series';
    }
}
?>
