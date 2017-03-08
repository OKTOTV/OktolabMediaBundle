<?php
namespace Oktolab\MediaBundle\Model;

use Bprs\CommandLineBundle\Model\BprsContainerAwareJob;

class ImportSeriesMetadataJob extends BprsContainerAwareJob
{
    private $logbook;
    private $media_service;
    private $keychain;
    private $jms_serializer;

    public function perform() {

        $this->logbook = $this->getContainer()->get('bprs_logbook');
        $this->logbook->info(
            'oktolab_media.series_start_metadata_import',
            [],
            $this->args['uniqID']
        );
        $this->keychain = $this->getContainer()->get('bprs_applink')->getKeychain($this->args['keychain']);
        if ($this->keychain) {
            $this->media_service = $this->getContainer()->get('oktolab_media');
            $local_series = $this->media_service->getSeries($this->args['uniqID']);

            if (filter_var($this->args['overwrite'], FILTER_VALIDATE_BOOLEAN) || $local_series == null) { // allow data overwrite or series isn't imported yet

                $this->jms_serializer = $this->getContainer()->get('jms_serializer');
                $series_class = $this->getContainer()->getParameter('oktolab_media.series_class');

                $response = $this->media_service->getResponse($this->keychain, MediaService::ROUTE_SERIES, ['uniqID' => $this->args['uniqID']]);
                $series = $this->jms_serializer->deserialize($response->getBody(), $series_class, 'json');
                if ($response->getStatusCode() == 200) {

                    if (!$local_series) {
                        $local_series = new $series_class;
                    }
                    $local_series->merge($series);
                    $local_series->setKeychain($this->keychain);

                    $em = $this->getContainer()->get('doctrine.orm.entity_manager');
                    $em->persist($local_series);
                    $em->flush();
                    $this->media_service->addImportSeriesPosterframeJob(
                        $this->args['uniqID'],
                        $this->keychain,
                        $series->getPosterframe()
                    );
                    // $this->media_service->dispatchImportedSeriesMetadataEvent($this->args);
                } else {
                    $this->logbook->error('oktolab_media.series_metadata_error_end_import', [], $this->args['uniqID']);
                }

                $this->logbook->info(
                    'oktolab_media.series_end_metadata_import',
                    [],
                    $this->args['uniqID']
                );
            }
            $this->media_service->dispatchImportedSeriesMetadataEvent($this->args);
        } else {
            $this->logbook->warning('oktolab_media.series_import_no_keychain', [], $this->args['uniqID']);
        }
    }

    public function getName()
    {
        return 'Import Series';
    }
}
?>
