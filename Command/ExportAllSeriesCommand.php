<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportAllSeriesCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('oktolab:media:export_all_series')
            ->setDescription('Allows export of all series to a given keychain')
            ->addArgument(
                'keychain',
                InputArgument::REQUIRED,
                'keychain you want to export to'
                )
            ->addOption(
                'overwrite',
                false,
                InputOption::VALUE_NONE,
                'if you want to overwrite existing data'
                )
            ->addOption(
                'active_only',
                false,
                InputOption::VALUE_NONE,
                'if you only want to export active series'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keychain = $this->getContainer()->get('bprs_applink')->getKeychain(
            $input->getArgument('keychain')
        );

        if ($keychain) {

            $repo = $this->getContainer()->get('oktolab_media')->getSeriesRepo();
            $seriess = null;
            if ($input->getOption('active_only')) {
                $seriess = $repo->findBy([
                    'isActive' => $input->getOption('active_only')
                ]);
            } else {
                $seriess = $repo->findAll();
            }

            $output->writeln(
                '<info>Starting Export Requests. This may take some time</info>'
            );
            foreach( $seriess as $series) {

                $success = $this
                    ->getContainer()
                    ->get('oktolab_keychain')
                    ->exportSeries(
                        $keychain,
                        $series->getUniqID(),
                        $input->getOption('overwrite')
                    );
                if ($success) {
                    $output->write('.');
                } else {
                    $outpu->writeln(
                        sprintf(
                            'UniqID [%s] unsuccessful!',
                            $series->getUniqID()
                            )
                    );
                }
            }
        } else {
            $output->writeln('No keychain found!');
        }
    }
}
