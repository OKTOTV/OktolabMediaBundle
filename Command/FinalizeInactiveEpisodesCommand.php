<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class FinalizeInactiveEpisodesCommand extends ContainerAwareCommand {

    public function __construct() {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:finalize_inactive_episodes')
            ->setDescription('Finalizes all inactive episodes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->getContainer()->get('oktolab_media')->getEpisodeRepository();
        $episodes = $repo->findBy(['isActive' => 0]);

        foreach($episodes as $episode) {
            $this->getContainer()->get('bprs_jobservice')->addJob(
            'Oktolab\MediaBundle\Model\FinalizeVideoJob',
                [
                    'uniqID'=> $episode->getUniqID()
                ]
            );
        }

        $output->writeln(sprintf('Added [%s] finalize Jobs', count($episodes)));
    }
}
