<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class FinalizeEpisodeCommand extends ContainerAwareCommand {

    public function __construct() {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:finalize_episode')
            ->setDescription('Finalizes an episode')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode')
            ->addOption('next', null, InputOption::VALUE_NONE, 'if you want to enqueue as next job instead of last');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('oktolab_media')->addFinalizeEpisodeJob(
            $input->getArgument('uniqID'),
            false,
            $input->getOption('next')
        );
    }
}
