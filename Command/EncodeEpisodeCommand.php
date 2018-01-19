<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EncodeEpisodeCommand extends ContainerAwareCommand {

    public function __construct() {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:encode_episode')
            ->setDescription('encodes an episode')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('oktolab_media')->addEncodeEpisodeJob(
            $input->getArgument('uniqID')
        );
    }
}
