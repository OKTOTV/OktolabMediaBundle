<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class GenerateThumbnailCaptionForAllCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('oktolab:media:generate_thumbnailsprites')
            ->setDescription('Generates Sprites for all episodes in database. warning: this could take a while.')
            ->addOption(
                'force',
                false,
                InputOption::VALUE_NONE,
                'if you truly want to execute'
                )
            ->addOption(
                'overwrite_existing',
                false,
                InputOption::VALUE_NONE,
                'if you also want to recreate sprites. (all episodes in database)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobservice = $this->getContainer()->get('bprs_jobservice');
        $repo = $this->getContainer()->get('oktolab_media')->getEpisodeRepository();
        $episodes = null;
        if ($input->getOption('overwrite_existing')) {
            $episodes = $repo->findAll();
        } else {
            $episodes = $repo->findWithoutSprites($this->getContainer()->getParameter('oktolab_media.episode_class'));
        }
        if ($input->getOption('force')) {
            foreach ($episodes as $episode) {
                $jobservice->addJob(
                    'Oktolab\MediaBundle\Model\GenerateThumbnailSpriteJob',
                    [
                        'uniqID'=> $episode->getUniqID()
                    ],
                    $this->getContainer()->getParameter('oktolab_media.sprite_worker_queue')
                );
                $output->write('.');
            }
            $output->writeln(sprintf('Added [%d] GenerateThumbnailSpriteJobs.', count($episodes)));
        } else {
            $output->writeln(sprintf('Will add [%d] GenerateThumbnailSpriteJobs. Are you sure?', count($episodes)));
        }
    }
}
