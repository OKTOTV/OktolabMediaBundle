<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Entity\Episode;


/**
 * @Route("/oktolab_media/public_api")
 */
class PublicApiController extends Controller
{
    /**
     * @Route("/episode/{uniqID}/{player_type}.{_format}", defaults={"_format": "json"}, requirements={"_format": "json"}, name="oktolab_media_player_for_episode")
     * @Method("GET")
     * @Template()
     */
    public function episodeAction($uniqID, $player_type)
    {
        $origin = $this->getParameter('oktolab_media.origin');
        $episode = $this->get('oktolab_media')->getEpisode($uniqID);
        return ['episode' => $episode, 'player_type' => $player_type];
    }

    /**
     * @Route("/series/{uniqID}/{player_type}.{_format}", defaults={"_format": "json"}, requirements={"_format": "json"}, name="oktolab_media_player_for_series")
     * @Method("GET")
     * @Template()
     */
    public function seriesAction($uniqID, $player_type)
    {
        $origin = $this->getParameter('oktolab_media.origin');
        $series = $this->get('oktolab_media')->getSeries($uniqID);
        return ['series' => $series, 'player_type' => $player_type, 'origin' => $origin];
    }

    /**
     * @TODO: migrate playlist functionality
     */
    // public function playlistAction($uniqID, $player_type)
    // {
    //     return [];
    // }

    /**
    * @Route("/origin/{player_type}.{_format}", defaults={"_format": "json"}, requirements={"_format": "json"}, name="oktolab_media_origin_for_episode")
    * @Method("GET")
    * Cache(expires="+1 day", public="yes")
    * @Template()
    */
    public function originAction($player_type)
    {
        $origin = $this->getParameter('oktolab_media.origin');
        return ['origin' => $origin, 'player_type' => $player_type];
    }
}
