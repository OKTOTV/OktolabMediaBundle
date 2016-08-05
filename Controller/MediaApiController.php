<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use JMS\Serializer\SerializationContext;


use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Entity\Episode;


/**
 * Handles all remote incoming actions and requests this application to provide.
 * You'll need an keychain with at least ROLE_OKTOLAB_MEDIA_READ to do anything here.
 * @Route("/api/oktolab_media")
 */
class MediaApiController extends Controller
{
    /**
     * @Route("/series.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * Cache(expires="+1 hour", public="yes")
     * @Method("GET")
     */
    public function listSeriesAction($format)
    {
        $seriess = $this->getDoctrine()->getManager()->getRepository($this->container->getParameter('oktolab_media.series_class'))->findAll();
        $jsonContent = $this->get('jms_serializer')->serialize($seriess, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }

    /**
     * @Route("/series/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showSeriesAction($uniqID, $format)
    {
        $em = $this->getDoctrine()->getManager();
        $series = $em->getRepository($this->container->getParameter('oktolab_media.series_class'))->findOneBy(array('uniqID' => $uniqID));
        $jsonContent = $this->get('jms_serializer')->serialize($series, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }

    /**
     * @Route("/episode/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showEpisodeAction($uniqID, $format)
    {
        $em = $this->getDoctrine()->getManager();
        $episode = $em->getRepository($this->container->getParameter('oktolab_media.episode_class'))->findOneBy(array('uniqID' => $uniqID));
        $jsonContent = $this->get('jms_serializer')->serialize($episode, $format, SerializationContext::create()->setVersion(1)->setGroups(["oktolab"]));
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }

    /**
     * @Route("/asset/{format}/{uniqID}", requirements={"uniqID"=".+","format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showAssetAction($uniqID, $format)
    {
        $em = $this->getDoctrine()->getManager();
        $asset = $em->getRepository($this->container->getParameter('bprs_asset.class'))->findOneBy(array('filekey' => $uniqID));
        $jsonContent = $this->get('jms_serializer')->serialize($asset, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }

    /**
     * @Route("/import/series")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_WRITE')")
     * @Method("POST")
     */
    public function importSeriesAction(Request $request)
    {
        //get usertoken, get url, use url + uniqid
        $uniqID = $request->request->get('id');
        if ($uniqID) {
            $apiuser = $this->get('security.context')->getToken()->getUser();
            $this->get('oktolab_media')->addSeriesJob($apiuser, $uniqID);
            return new Response("", Response::HTTP_ACCEPTED);
            //and send OktolabMediaBundle worker to import an entire series
        }
        return new Response("", Response::BAD_REQUEST);
    }

    /**
     * @Route("/import/episode", name="oktolab_media_import_episode")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_WRITE')")
     * @Method("POST")
     */
    public function importEpisodeAction(Request $request)
    {
        $uniqID = $request->request->get('id');
        if ($uniqID) {
            $apiuser = $this->get('security.context')->getToken()->getUser();
            $this->get('oktolab_media')->addEpisodeJob($apiuser, $uniqID);
            return new Response("", Response::HTTP_ACCEPTED);
        }
        return new Response("", Response::BAD_REQUEST);
    }
}
