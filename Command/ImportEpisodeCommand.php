<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ImportEpisodeCommand extends ContainerAwareCommand {

    public function __construct() {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:import_episode')
            ->setDescription('Imports an episode from remote application')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'if you want to overwrite existing data')
            ->addOption('keychain', false, InputOption::VALUE_REQUIRED, 'the uniqID of your keychain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keychain = null;
        if (!$input->getOption('keychain')) {
            $keychain = $this->getContainer()->get('oktolab_media')->getEpisode(
                $input->getArgument('uniqID')
            )->getKeychain();
        } else {
            $keychain = $this->getContainer()->get('bprs_applink')->getKeychain(
                $input->getOption('keychain')
            );
        }

        $this->getContainer()->get('oktolab_media')->addEpisodeJob(
            $keychain,
            $input->getArgument('uniqID'),
            $input->getOption('overwrite')
        );
    }
}
