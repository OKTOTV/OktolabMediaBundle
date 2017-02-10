<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Form\SeriesType;
use Bprs\AppLinkBundle\Entity\Keychain;
use Oktolab\MediaBundle\Model\MediaService;
use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Series controller.
 *
 * @Route("/oktolab_media/series")
 */
class SeriesController extends Controller
{

    /**
     * Lists all Series entities.
     *
     * @Route("/{page}", name="oktolab_series", defaults={"page" = 1}, requirements={"page": "\d+"})
     * @Template()
     */
    public function indexAction($page)
    {
        $em = $this->getDoctrine()->getManager();
        $class = $this->container->getParameter("oktolab_media.series_class");
        $dql = "SELECT s, p FROM ".$class." s LEFT JOIN s.posterframe p";
        $query = $em->createQuery($dql);
        $paginator  = $this->get('knp_paginator');
        $seriess = $paginator->paginate(
            $query,
            $page,
            5
        );

        return array('seriess' => $seriess);
    }

    /**
     * Displays a form to create a new Series entity.
     *
     * @Route("/new", name="oktolab_series_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $series = $this->get('oktolab_media')->createSeries();
        $form = $this->createForm(SeriesType::class, $series);
        $form->add('submit', SubmitType::class, ['label' => 'oktolab_media.new_series_button', 'attr' => ['class' => 'btn btn-primary']]);

        if ($request->getMethod() == "POST") { //sends form
            $form->handleRequest($request);
            $em = $this->getDoctrine()->getManager();
            if ($form->isValid()) { //form is valid, save or preview
                if ($form->get('submit')->isClicked()) { //save me
                    $em->persist($series);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_create_series');
                    return $this->redirect($this->generateUrl('oktolab_series_show', ['series' => $series->getUniqId()]));
                } else { //???
                    $this->get('session')->getFlashBag()->add('success', 'oktolab_media.unknown_action_series');
                    return $this->redirect($this->generateUrl('oktolab_series'));
                }
            }
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_create_series');
        }

        return ['form' => $form->createView()];
    }

    /**
     * Finds and displays a Series entity.
     *
     * @ParamConverter("series", class="OktolabMediaBundle:Series")
     * @Method("GET")
     * @Template()
     */
    public function showAction($series)
    {
        return ['series' => $series];
    }

    /**
     * Displays a form to edit an existing Series entity.
     *
     * @ ParamConverter("series", class="OktolabMediaBundle:Series")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, $series)
    {
        $series = $this->get('oktolab_media')->getSeries($series);
        $form = $this->createForm(SeriesType::class, $series);
        $form->add('submit', SubmitType::class, ['label' => 'oktolab_media.edit_series_button', 'attr' => ['class' => 'btn btn-primary']]);
        $form->add('delete', SubmitType::class, ['label' => 'oktolab_media.delete_series_button', 'attr' => ['class' => 'btn btn-danger']]);

        if ($request->getMethod() == "POST") { //sends form
            $form->handleRequest($request);
            $em = $this->getDoctrine()->getManager();
            if ($form->isValid()) { //form is valid, save or preview
                if ($form->get('submit')->isClicked()) { //save me
                    $em->persist($series);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_edit_series');
                    return $this->redirect($this->generateUrl('oktolab_series_show', ['series' => $series->getUniqID()]));
                } elseif ($form->get('delete')->isClicked()) {
                    $this->get('oktolab_media_helper')->deleteSeries($series);
                    $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_delete_series');
                    return $this->redirect($this->generateUrl('oktolab_series'));
                } else { //???
                    $this->get('session')->getFlashBag()->add('success', 'oktolab_media.unknown_action_series');
                    return $this->redirect($this->generateUrl('oktolab_series_show', ['series' => $series->getUniqID()]));
                }
            }
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_edit_series');
        }

        return ['form' => $form->createView()];
    }

    /**
    * @Route("/{series}/export", name="oktolab_media_export_series")
    * @Method("GET")
    * @Template()
    * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
    */
    public function exportAction(Request $request, $series)
    {
        $send = $request->query->get('keychain', false);
        if ($send) { // clicked send to remote application
            $keychain = $this->get('bprs_applink')->getKeychain($send);
            $success = $this->get('oktolab_keychain')->exportSeries($keychain, $series);
            if ($success) {
                $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_export_series');
                return $this->redirect($this->generateUrl('oktolab_media_export_series', ['series' => $uniqID]));
            } else {
                $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_export_series');
            }
        }

        $keychains = $this->get('bprs_applink')->getKeychainsWithRole(MediaService::ROLE_WRITE);
        $series = $this->get('oktolab_media')->getSeries($series);
        return ['series' => $series, 'keychains' => $keychains];
    }

    /**
     * Browse Series of an remote application (with the keychain)
     * @Route("/remote/{keychain}", name="oktolab_media_remote_seriess")
     * @Method("GET")
     * @Template()
     */
    public function listRemoteSeriess(Keychain $keychain)
    {
        $series_url = $this->get('bprs_applink')->getApiUrlsForKey($keychain, 'oktolab_media_api_list_series');
        if ($series_url) {
            $client = new Client();
            $response = $client->request('GET', $series_url, ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]);
            if ($response->getStatusCode() == 200) {
                $info = json_decode(html_entity_decode((string)$response->getBody()),true);
                return ['result' => $info, 'keychain' => $keychain];
            }
        }
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/import/{keychain}", name="oktolab_media_import_remote_series")
     * @Method("GET")
     */
    public function importSeriesAction(Request $request, Keychain $keychain)
    {
        $uniqID = $request->query->get('uniqID');
        if ($uniqID) {
            $this->get('oktolab_media')->addSeriesJob($keychain, $uniqID);
            return new Response('', Response::HTTP_ACCEPTED);
        }
        return new Response('', Response::HTTP_BAD_REQUEST);
    }
}
