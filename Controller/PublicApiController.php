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
use Oktolab\MediaBundle\Entity\Stream;

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
     *     "/caption",
     *     name="oktolab_media_caption_for_episode"
     * )
     * @Method("GET")
     */
    public function captionAction(Request $request)
    {
        $uniqID = $request->query->get('uniqID');
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
     * @Route("/sprite", name="oktolab_media_sprite_for_episode")
     * @Method("GET")
     */
    public function spriteAction(Request $request)
    {
        $uniqID = $request->query->get('uniqID', false);
        $episode = $this->get('oktolab_media')->getEpisode($uniqID);
        $sprite = $episode->getSprite();
        if ($sprite) {
            $response = new Response();
            $response->headers->set('Content-Type', "text/vtt");
            $response->headers->set(
                'Content-Disposition',
                'attachment; filename='.$uniqID.'.vtt'
            );
            $response->sendHeaders();
            $response->setContent($this->get('oktolab_sprite')->getSpriteWebvttForEpisode($episode, $request->query->get('player_type', 'jwplayer')));
            return $response;
        }

        return new Response("", Response::HTTP_BAD_REQUEST);
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
     * TODO: oneOrNone result, respond with empty embed
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

    /**
     * @Route("/check_streamstatus", name="oktolab_media_check_streamstatus")
     * @Method("GET")
     */
    public function checkStreamStatusAction(Request $request)
    {
        $stream_service = $this->get('oktolab_media_stream');
        $stream = $stream_service->getStream($request->query->get('name'));
        if ($stream) {
            return new Response($stream->getTechnicalStatus(), Response::HTTP_OK);
        }
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * can be used to check if the rtmp stream of nginx is allowed to send
     * @Route("/check_streamkey", name="oktolab_media_check_streamkey")
     * @Method({"POST"})
     */
    public function checkStreamkeyAction(Request $request)
    {
        $streamkey = $request->get('name', false);
        if ($streamkey === false) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }
        $streamservice = $this->get('oktolab_media_stream');
        $stream = $streamservice->getStream($streamkey);

        if ($stream) {
            $this->get('bprs_logbook')->info(
                'oktolab_media.stream_checked_streamkey',
                ['streamkey' => $streamkey], $stream->getUniqID());
            $streamservice->setStreamStatus($stream, Stream::STATE_RECEIVING);
            return new Response('', Response::HTTP_OK);
        }

        return new Response('', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @Route("/end_stream", name="oktolab_media_end_stream")
     * @Method("POST")
     */
    public function endStreamAction(Request $request)
    {
        # code...
        // TODO: send out stream ended event (with series with streamkey)
        // WORKER grab recorded streamfile, create episode, encode episode.
        $streamkey = $request->get('name', false);
        if ($streamkey === false) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $streamservice = $this->get('oktolab_media_stream');
        $stream = $streamservice->getStream($streamkey);

        if ($stream) {
            $this->get('bprs_logbook')->info(
                'oktolab_media.stream_end',
                ['streamkey' => $streamkey],
                $stream->getUniqID()
            );
            $streamservice->setStreamStatus($stream, Stream::STATE_ENDED);
            return new Response('', Response::HTTP_OK);
        }

        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}
