<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteEpisodeCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('oktolab:media:delete_episode')
            ->setDescription('Removes an episode, its media, posterframe and video')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode')
            ->addOption('force', false, InputOption::VALUE_NONE, 'execute this command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $episode = $this
            ->getContainer()
            ->get('oktolab_media')
            ->getEpisode($input->getArgument('uniqID'));

        if ($episode) {
            if ($input->getOption('force')) {
                $this
                    ->getContainer()
                    ->get('oktolab_media_helper')
                    ->deleteEpisode($episode);
            } else {
                $output->writeln('To truly delete this episode, use --force!');
            }
        } else {
            $output->writeln(
                sprintf(
                    'No Episode with uniqID [%s] found',
                    $input->getArgument('uniqID')
                )
            );
        }
    }
}
