<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
* @Route("/oktolab_media")
*/
class MediaController extends Controller
{
    /**
     * @Route("/download/{key}", requirements={"key"=".+"}, name="oktolab_media_download")
     * @Method("GET")
     */
    public function downloadAsset($key)
    {
        $asset = $this->getDoctrine()->getManager()->getRepository($this->container->getParameter('bprs_asset.class'))->findOneBy(array('key' => $key));
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
