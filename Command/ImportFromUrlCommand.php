<?php

namespace Oktolab\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use GuzzleHttp\Client;

class ImportFromUrlCommand extends ContainerAwareCommand {

    private $client;
    private $media_service;
    private $em;
    private $info;
    private $name;

    public function __construct($media_service, $em)
    {
        $this->media_service = $media_service;
        $this->em = $em;
        $this->client = new Client();
        $this->name = false;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oktolab:media:import_from_url')
            ->setDescription('Imports an episode from remote url. If you want to import from an other oktolab media application, use import_episode.')
            ->addArgument('url', InputArgument::REQUIRED, 'the url to your video')
            ->addOption('name', false, InputOption::VALUE_REQUIRED, 'if you want to set a specific name')
            ->addOption('getLink', false, InputOption::VALUE_NONE, 'returns the link only (usefull for yt and debugging)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('name')) {
            $this->name = $input->getOption('name');
        }

        $episode = $this->media_service->createEpisode();
        $episode->setName($this->name);
        $this->em->persist($episode);
        $this->em->flush();
        $this->media_service->addImportEpisodeFileFromUrlJob(
            $episode->getUniqID(),
            $input->getArgument('url')
        );
    }
}
?>
