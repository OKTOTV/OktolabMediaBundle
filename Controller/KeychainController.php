<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Oktolab\MediaBundle\Model\MediaService;

use Bprs\AppLinkBundle\Entity\Keychain;
/**
 * Keychain controller. Allows backend importing onsite.
 *
 * @Route("/oktolab_media")
 * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
 */
class KeychainController extends Controller
{
    /**
     * @Route("/list_keychains", name="oktolab_media_list_keychains")
     * @Template()
     */
    public function listKeychainsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $keys = $em->getRepository('BprsAppLinkBundle:Key')->findBy(['role' => MediaService::ROLE_READ]);

        return array('keys' => $keys);
    }

    /**
     * @Route("/show_keychain/{keychain}", name="oktolab_media_show_keychain")
     * @Template()
     */
    public function showKeychainAction(Request $request, Keychain $keychain)
    {
        $url = $request->query->get('url', null);
        $response = $this->get('oktolab_keychain')->getSeriess($keychain, $url);
        return ['keychain' => $keychain, 'response' => $response];
    }

    /**
     * @Route("/show_keychain/{keychain}/series/{uniqID}", name="oktolab_media_show_keychain_series")
     * @Template()
     */
    public function showSeriesAction(Keychain $keychain, $uniqID)
    {
        $series = $this->get('oktolab_keychain')->getSeries(
            $keychain,
            $uniqID
        );
        return ['keychain' => $keychain, 'series' => $series];
    }

    /**
     * @Route("/show_keychain/{keychain}/episode/{uniqID}", name="oktolab_media_show_keychain_episode")
     * @Template()
     */
    public function showEpisodeAction(Keychain $keychain, $uniqID)
    {
        $episode = $this->get('oktolab_keychain')->getEpisode(
            $keychain,
            $uniqID
        );
        return  ['keychain' => $keychain, 'episode' => $episode];
    }

    /**
     * @Route("/import/episode", name="oktolab_media_local_import_episode")
     * @Method("POST")
     */
    public function importEpisodeAction(Request $request)
    {
        if ($request->request->get('uniqID')) {
            $user = $request->request->get('user');
            $em = $this->getDoctrine()->getManager();
            $apiuser = $em->getRepository('BprsAppLinkBundle:Keychain')
                ->findOneBy(['user'=> $user]);

            $this->get('oktolab_media')->addEpisodeJob(
                $apiuser,
                $request->request->get('uniqID'),
                $request->request->get('overwrite', false)
            );
            return new Response("", Response::HTTP_ACCEPTED);
        }
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/import/series", name="oktolab_media_local_import_series")
     * @Method("POST")
     */
    public function importSeriesAction(Request $request)
    {
        if ($request->request->get('uniqID')) {
            $user = $request->request->get('user');
            $em = $this->getDoctrine()->getManager();
            $apiuser = $em->getRepository('BprsAppLinkBundle:Keychain')
                ->findOneBy(['user'=> $user]);

            $this->get('oktolab_media')->addSeriesJob(
                $apiuser,
                $request->request->get('uniqID'),
                $request->request->get('overwrite', false)
            );
            return new Response("", Response::HTTP_ACCEPTED);
        }
        return new Response("", Response::HTTP_BAD_REQUEST);
    }

    public function compareEpisode($uniqID)
    {
        $episode = $this->get('oktolab_media')->getEpisode($uniqID);
        $remote_episode = $this->get('oktolab_keychain')->getEpisode(
            $keychain,
            $uniqID
        );
        return ['episode' => $episode, 'remote_episode' => $remote_episode];
    }
} ?>
