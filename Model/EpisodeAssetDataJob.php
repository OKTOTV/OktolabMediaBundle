<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;
use Oktolab\MediaBundle\Entity\Media;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Event\EncodedEpisodeEvent;
use Oktolab\MediaBundle\OktolabMediaEvent;

/**
 * checks original video and starts encoding according to configurated resolutions.
 * after encoding, checks availability
 */
class EpisodeAssetDataJob extends BprsContainerAwareJob
{
    private $em;
    private $logbook;

    public function perform() {
        $episode = $this->getContainer()->get('oktolab_media')->getEpisode($this->args['uniqID']);
        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info(
            'oktolab_media.episode_start_assetdatajob',
            [],
            $this->args['uniqID']
        );

        if ($episode) {
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
            if (!$episode->getVideo()) {
                $this->logbook->info(
                    'oktolab_media.episode_assetdatajob_novideo',
                    [],
                    $this->args['uniqID']
                );
            } else {
                // get Medatinfo of episode video
                $metainfo = $this->getStreamInformationsOfEpisode($episode);
                if ($metainfo) {
                    $episode->setDuration($metainfo['video']['duration']);
                    $this->em->persist($episode);
                    $this->em->flush();

                    $this->logbook->info(
                        'oktolab_media.episode_end_assetdatajob',
                        [],
                        $this->args['uniqID']
                    );

                    $this->getContainer()->get('oktolab_media')->dispatchEpisodeAssetDataEvent($metainfo);
                } else {
                    $this->logbook->info(
                        'oktolab_media.episode_assetdatajob_cantreadmeainfos',
                        ['%url%' => $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo())],
                        $this->args['uniqID']
                    );
                }
            }
        } else {
            $this->logbook->error(
                'oktolab_media.episode_assetdatajob_noepisode',
                [],
                $this->args['uniqID']
            );
        }
    }

    public function getName() {
        return 'Episode Asset Metadata';
    }

    /**
     * extracts streaminformations of an episode from its video.
     * returns false if informations can't be extracted
     * returns array of video and audio info.
     */
    private function getStreamInformationsOfEpisode($episode)
    {
        $uri = $this->getContainer()->get('bprs.asset_helper')->getAbsoluteUrl($episode->getVideo());
        if (!$uri) { // can't create uri of episode video
            $this->logbook->error('oktolab_media.episode_assetdatajob_nourl', [], $this->args['uniqID']);
            return false;
        }
        $metadata = ['video' => false, 'audio' => false];
        try {
            $metainfo = json_decode(shell_exec(sprintf('ffprobe -v error -show_streams -print_format json %s', $uri)), true);
            foreach ($metainfo['streams'] as $stream) {
                if ($metadata['video'] && $metadata['audio']) {
                    break;
                }
                if ($stream['codec_type'] == "audio") {
                    $metadata['audio'] = $stream;
                }
                if ($stream['codec_type'] == "video") {
                    $metadata['video'] = $stream;
                }
            }
        } catch (Exception $e) {
            $metadata = null;
        }

        return $metadata;
    }
}
