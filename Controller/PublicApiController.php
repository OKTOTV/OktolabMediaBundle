<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Entity\Episode;


/**
 * @Route("/oktolab_media/api/public")
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
     * @Route(
     *     "/caption/{uniqID}.{_format}",
     *     defaults={"_format": "vtt"},
     *     requirements={"_format": "vtt"},
     *     name="oktolab_media_caption_for_episode"
     * )
     * @Method("GET")
     */
    public function captionAction($uniqID)
    {
        $caption = $this->get('oktolab_media')->getCaption($uniqID);

        $response = new Response();
        $response->headers->set('Content-Type', "text/vtt");
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="'.$caption->getLabel().'.vtt"'
        );
        $response->sendHeaders();
        $response->setContent($caption->getContent());
        return $response;
    }

    /**
    * @Route("/origin.{_format}",
    *   defaults={"_format": "json"},
    *   requirements={"_format": "json"},
    *   name="oktolab_media_origin_for_episode"
    *   )
    * @Method("GET")
    * @Template()
    */
    public function originAction(Request $request)
    {
        $origin = $this->getParameter('oktolab_media.origin');
        return [
            'origin' => $origin,
            'player_type' => $request->query->get('player_type', 'jwplayer')
        ];
    }

    /**
     * TODO: oneOrNone result, respond with empy embed
     * @Route("/embed/episode", name="oktolab_media_embed_episode")
     * @Method("GET")
     * @Template()
     */
    public function embedEpisodeAction(Request $request)
    {
        $uniqID = $request->query->get('uniqID');
        $episode = $this->get('oktolab_media')->getEpisode($uniqID);
        return ['episode' => $episode];
    }

    // /**
    //  * @Route("/posterframe/series", name="oktolab_media_series_show_posterframe")
    //  * @Method("GET")
    //  * @Template()
    //  */
    // public function showSeriesPosterframeAction(Request $request)
    // {
    //     $uniqID = $request->query->get('uniqID');
    //     if ($uniqID) {
    //         $series = $this->get('oktolab_media')->getSeries($uniqID);
    //         if ($series) {
    //             if ($series->getPosterframe()) {
    //                 return new RedirectResponse(
    //                     $this->get('bprs.asset_helper')->getAbsoluteUrl($series->getPosterframe())
    //                 );
    //             }
    //             return new RedirectResponse(
    //                 $this->get('bprs.asset_helper')->get404()
    //             );
    //         }
    //     }
    //     return new Response("", Response::HTTP_BAD_REQUEST);
    // }
}
