<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportSeriesJob extends BprsContainerAwareJob
{
    public function perform() {
        echo "Start series import\n";
        $oktolabMediaSerivce = $this->getContainer()->get('oktolab_media');
        $keychain = $this->getContainer()->getDoctrine()->getManager()->getRepository('BprsAppLinkBundle:Keychain')->findOneBy(array('url' => $this->args['url']));
        if ($keychain) {
            $oktolabMediaService->importSeries($keychain, $this->args['uniqID']);
            echo "End of series import\n";
        }
        echo "No keychain found! Abort action\n";
    }
}
?>
