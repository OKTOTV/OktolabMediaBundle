<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportSeriesCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('oktolab:media:export_series')
            ->setDescription('Allows export of an series to a given keychain')
            ->addArgument('keychain', InputArgument::REQUIRED, 'keychain you want to export to')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your series')
            ->addOption('overwrite', false, InputOption::VALUE_NONE, 'if you want to overwrite existing data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keychain = $this->getContainer()->get('bprs_applink')->getKeychain($input->getArgument('keychain'));
        $series = $this->getContainer()->get('oktolab_media')->getSeries($input->getArgument('uniqID'));

        if ($keychain && $series) {

            $success = $this
                ->getContainer()
                ->get('oktolab_keychain')
                ->exportSeries(
                    $keychain,
                    $input->getArgument('uniqID'),
                    $input->getOption('overwrite')
                );

            if ($success) {
                $output->writeln('The ExportJob was accepted');
            } else {
                $output->writeln('The ExportJob was not accepted! Something went wrong');
            }

        } else {
            $output->writeln('No keychain or series found!');
        }
    }
}
