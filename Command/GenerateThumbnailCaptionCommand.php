<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class GenerateThumbnailCaptionCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('oktolab:media:generate_thumbnailsprite_for_episode')
            ->setDescription('Generates Sprite and Captiontrack for given episode')
            ->addArgument('uniqID', InputArgument::REQUIRED, 'the uniqID of your episode.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('bprs_jobservice')->addJob(
            'Oktolab\MediaBundle\Model\GenerateThumbnailSpriteJob',
            [
                'uniqID'=> $input->getArgument('uniqID')
            ],
            $this->getContainer()->getParameter('oktolab_media.sprite_worker_queue')
        );
    }
}
