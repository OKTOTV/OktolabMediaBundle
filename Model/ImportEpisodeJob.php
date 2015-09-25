<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportEpisodeJob extends BprsContainerAwareJob
{
    public function perform() {
        echo "Start episode import\n";
        $oktolabMediaService = $this->getContainer()->get('oktolab_media');
        $keychain = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('BprsAppLinkBundle:Keychain')->findOneBy(array('user' => $this->args['user']));
        if ($keychain) {
            $oktolabMediaService->importEpisode($keychain, $this->args['uniqID']);
            echo "End of episode import\n";
        } else{
            echo "No keychain found! Abort action\n";
        }
    }
}
?>
