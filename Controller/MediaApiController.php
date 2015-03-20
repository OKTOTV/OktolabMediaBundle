<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

use Oktolab\DelorianBundle\Entity\Series as DelorianSeries;
use Oktolab\MediaBundle\Entity\Series;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/api")
 */
class MediaApiController extends Controller
{
    /**
     * @Route("/series.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Cache(expires="+1 day", public="yes")
     * @Method("GET")
     */
    public function listSeriesAction($format)
    {
        $seriess = $this->getDoctrine()->getManager()->getRepository('OktolabMediaBundle:Series')->findAll();
        $jsonContent = $this->get('jms_serializer')->serialize($seriess, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    }

    /**
     * @Route("/import/series.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Method("POST")
     */
    public function importSeriesAction(Request $request, $format)
    {
        $asdf = '{"name":"New Ordner","description":"New Ordner entf\u00fchrt die ZuseherInnen immer wieder auf eine spannende Reise in die wundersame Welt der Netzkultur. In Doppelconf\u00e9rence pr\u00e4sentieren die Moderatoren Kurioses, Wissenschaftliches und Unterhaltsames. Dinge, die man au\u00dferhalb des Internets nie zu sehen bekommt \u2013 au\u00dfer nat\u00fcrlich bei New Ordner und selbst da sieht man sie eigentlich nicht\u2026 \r\n \r\n","created_at":"2009-10-07T07:44:27+0200","updated_at":"2012-01-24T12:48:47+0100"}';
        $series = $this->get('jms_serializer')->deserialize($asdf, "Oktolab\MediaBundle\Entity\Series", $format);

        return new Response($series->getDescription(), 200, array('Content-Type' => 'text/html; charset=utf8'));
    }

    /**
     * @Route("/series/{id}.{format}", defaults={"format": "json"}, requirements={"format": "json|xml"})
     * @Method("GET")
     */
    public function showSeriesAction(Request $request, DelorianSeries $old_series, $format)
    {
        $series = new Series();
        $series->setName($old_series->getTitle());
        $series->setDescription($old_series->getAbstractTextPublic());
        $series->setCreatedAt($old_series->getCreatedAt());
        $series->setUpdatedAt($old_series->getUpdatedAt());

        $jsonContent = $this->get('jms_serializer')->serialize($series, $format);
        return new Response($jsonContent, 200, array('Content-Type' => 'application/json; charset=utf8'));
    }
}
