<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Entity\Episode;


/**
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
        $seriess = $this->getDoctrine()->getManager()->getRepository('OktolabMediaBundle:Series')->findAll();
        $jsonContent = $this->get('jms_serializer')->serialize($seriess, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }

    /**
     * @Route("/series/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showSeriesAction(Series $series, $format)
    {
        $jsonContent = $this->get('jms_serializer')->serialize($series, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }

    /**
     * @Route("/episode/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function showEpisodeAction(Episode $episode, $format)
    {
        $jsonContent = $this->get('jms_serializer')->serialize($episode, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }

    /**
     * @Route("/import/series/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml", "id": "\d+"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_WRITE')")
     * @Method("POST")
     */
    public function importSeriesAction($uniqID)
    {
        //get usertoken, get url, use url + uniqid
        $apiuser = $this->get('security.context')->getToken()->getUser();
        $this->get('oktolab_media')->addSeriesJob($apiuser, $uniqID);
        return new Response("", Response::HTTP_ACCEPTED);
        //and send OktolabMediaBundle worker to import an entire series
    }

    /**
     * @Route("/import/episode/{uniqID}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml", "id": "\d+"})
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_WRITE')")
     * @Method("POST")
     */
    public function importEpisodeAction($uniqID)
    {
        //get usertoken, get url, use url + uniqid
        $apiuser = $this->get('security.context')->getToken()->getUser();
        $this->get('oktolab_media')->addEpisodeJob($apiuser, $uniqID);
        return new Response("", Response::HTTP_ACCEPTED);
        //and send OktolabMediaBundle worker to import an episode
    }

    /**
     * @Route("/asset/{key}")
     * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
     * @Method("GET")
     */
    public function downloadAsset($key)
    {
        $asset = $this->getDoctrine()->getManager()->getRepository('OktolabMediaBundle:Asset')->findOneBy(array('key' => $key));
        if ($this->container->getParameter('xsendfile')) {
            $response = new Response();
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $asset->getName()));
            $response->headers->set('Content-type', $asset->getMimetype());
            $response->headers->set('X-Sendfile', $this->get('bprs.asset_helper')->getPath($asset));
            $response->sendHeaders();
            return $response;
        }
        $response = new Response();
        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', $asset->getMimetype());
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"',$asset->getName()));
        $response->headers->set('Content-length', filesize($this->get('bprs.asset_helper')->getPath($asset)));

        // // Send headers before outputting anything
        $response->sendHeaders();
        $response->setContent(readfile($this->get('bprs.asset_helper')->getPath($asset)));

        return $response;

    }
}
