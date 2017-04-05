<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ImportEpisodePosterframeCommand extends ContainerAwareCommand {

    public function __construct() {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:import_episode_posterframe')
            ->setDescription('Imports an episode posterframe from remote application. Overwrites existing poserframe')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'the queue you want to add this job to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('oktolab_media')->addEpisodePosterframeJob(
            $input->getArgument('uniqID'),
            $input->getOption('queue')
        );
    }
}
