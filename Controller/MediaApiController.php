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
     * @Route("/list/series.{_format}", defaults={"_format": "json"}, requirements={"_format": "json|html"}, name="oktolab_media_api_list_series")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     * @Template()
     */
    public function listSeriesAction(Request $request, $_format)
    {
        $series_class = $this->container->getParameter('oktolab_media.series_class');
        $query = $this->getDoctrine()
            ->getManager()
            ->getRepository($series_class)
            ->findActive($series_class, true);

        $paginator = $this->get('knp_paginator');

        $seriess = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        $seriess->setUsedRoute('oktolab_media_api_list_series');
        $seriess->setParam('_format', $_format);

        return ['seriess' => $seriess, 'serialization_group' => $request->query->get('group')];
    }

    /**
     * @Route("/series.{_format}", defaults={"_format": "json"}, requirements={"_format": "json|xml"}, name="oktolab_media_api_show_series")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     * @Template()
     */
    public function showSeriesAction(Request $request, $_format)
    {
        $uniqID = $request->query->get('uniqID');
        if ($uniqID) {
            $em = $this->getDoctrine()->getManager();
            $series = $this->get('oktolab_media')->getSeries($uniqID);
            // $jsonContent = $this->get('jms_serializer')->serialize($series, $_format);
            return ['series' => $series, 'serialization_group' => $request->query->get('group')];
        }
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/episodes.{_format}",  defaults={"_format": "json"}, requirements={"_format": "json"}, name="oktolab_media_api_list_episodes")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     * @Template()
     */
    public function listEpisodesAction(Request $request, $_format)
    {
        $episode_class = $this->container->getParameter('oktolab_media.episode_class');
        $query = $this->getDoctrine()
            ->getManager()
            ->getRepository($episode_class)
            ->findActive($episode_class, true);

        $paginator = $this->get('knp_paginator');

        $episodes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        $episodes->setUsedRoute('oktolab_media_api_list_episodes');
        $episodes->setParam('_format', $_format);
        return ['episodes' => $episodes, 'serialization_group' => $request->query->get('group')];
    }

    /**
     * @Route("/episode.{_format}", defaults={"_format": "json"}, requirements={"_format": "json|xml"}, name="oktolab_media_api_show_episode")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     * @Template()
     */
    public function showEpisodeAction(Request $request, $_format)
    {
        $uniqID = $request->query->get('uniqID');
        if ($uniqID) {
            $em = $this->getDoctrine()->getManager();
            $episode = $this->get('oktolab_media')->getEpisode($uniqID);
            return ['episode' => $episode, 'serialization_group' => $request->query->get('group')];
        }
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/asset.{_format}", requirements={"_format": "json|xml"}, name="oktolab_media_api_show_asset")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showAssetAction(Request $request, $_format)
    {
        $filekey = $request->query->get('filekey');
        if ($filekey) {
            $em = $this->getDoctrine()->getManager();
            $asset = $em->getRepository($this->container->getParameter('bprs_asset.class'))->findOneBy(array('filekey' => $filekey));
            $jsonContent = $this->get('jms_serializer')->serialize($asset, $_format);
            return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
        }
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/import/series", name="oktolab_media_api_import_series")
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
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/import/episode", name="oktolab_media_api_import_episode")
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
