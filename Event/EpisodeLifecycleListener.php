<?php

namespace Oktolab\MediaBundle\Event;

use Symfony\Component\DependencyInjection\Container;
use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\EpisodeLifecycleEvent;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

class EpisodeLifecycleListener {

    private $logger;
    private $dispatcher;
    private $oktolab_media;
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @ORM\PostPersist
     */
    public function postPersistHandler($episode, LifecycleEventArgs $event)
    {

        $this->container->get('bprs_logbook')->info(
            'oktolab_media.event_episode_created',
            ['%episode%' => $episode],
            $episode->getUniqID()
        );
        $event = new EpisodeLifecycleEvent($episode);
        $this->container->get('event_dispatcher')->dispatch(OktolabMediaEvent::CREATED_EPISODE, $event);
        $this->container->get('oktolab_media')->addEncodeVideoJob($episode->getUniqID());
    }
}

?>
