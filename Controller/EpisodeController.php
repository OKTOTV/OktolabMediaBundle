<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Form\EpisodeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Bprs\AppLinkBundle\Entity\Keychain;
use GuzzleHttp\Client;
use Oktolab\MediaBundle\Model\MediaService;

/**
 * Episode controller.
 *
 * @Route("/oktolab_media/episode")
 */
class EpisodeController extends Controller
{
    /**
     * Creates a new Episode entity.
     *
     * @Route("/", name="oktolab_episode_create")
     * @Method("POST")
     * @Template("OktolabMediaBundle:Episode:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = $this->get('oktolab_media')->createEpisode();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'oktolab_media.success_create_episode'
            );

            return $this->redirect(
                $this->generateUrl(
                    'oktolab_episode_show',
                    ['uniqID' => $entity->getUniqID()]
                )
            );
        } else {
            $this->get('session')->getFlashBag()->add(
                'error',
                'oktolab_media.error_create_episode'
            );
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * @Route("s", name="oktolab_episode_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $results = $request->query->get('results', 10);
        $em = $this->getDoctrine()->getManager();
        $class = $this->container->getParameter('oktolab_media.episode_class');
        if ($request->query->get('inactive_only', "0")) {
            $query = $em->getRepository($class)->findInactiveEpisodesAction($class, true);
        } else {
            $query = $em->getRepository($class)->findAllForClass($class, true);
        }
        $paginator = $this->get('knp_paginator');
        $episodes = $paginator->paginate($query, $page, $results);

        return ['episodes' => $episodes];
    }

    /**
     * Creates a form to create a Episode entity.
     *
     * @param Episode $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Episode $entity)
    {
        $form = $this->createForm(EpisodeType::class, $entity, array(
            'action' => $this->generateUrl('oktolab_episode_create'),
            'method' => 'POST',
        ));

        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'oktolab_media.new_episode_create_button',
                'attr' => ['class' => 'btn btn-primary']
            ]
        );

        return $form;
    }

    /**
     * Displays a form to create a new Episode entity.
     *
     * @Route("/new", name="oktolab_episode_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {
        $entity = new Episode();
        $form   = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * @Route("/finalize", name="oktolab_episode_finalize")
     * @Method({"GET"})
     */
    public function finalizeEpisodeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $uniqID = $request->query->get('uniqID', false);
        if ($uniqID) {
            $oktolab_media = $this->get('oktolab_media');
            $episode = $oktolab_media->getEpisode($uniqID);
            if ($episode) {
                $oktolab_media->addFinalizeEpisodeJob($uniqID, false, true);
                $this->get('session')->getFlashBag()->add(
                    'info',
                    'oktolab_media.episode_finalize_info'
                );
                return $this->redirect(
                    $this->generateUrl(
                        'oktolab_episode_show',
                        ['uniqID' => $uniqID]
                    )
                );
            } else {
                $this->get('session')->getFlashBag()->add(
                    'info',
                    'oktolab_media.episode_not_found_info'
                );
            }
        } else {
            $this->get('session')->getFlashBag()->add(
                'info',
                'oktolab_media.episode_not_found_info'
            );
        }

        return $this->redirect(
            $this->generateUrl(
                'oktolab_episode_index'
            )
        );
    }

    /**
     * Finds and displays a Episode entity.
     *
     * @Route("/{uniqID}", name="oktolab_episode_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($uniqID)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(
            $this->container->getParameter('oktolab_media.episode_class')
        )->findOneBy(array('uniqID' => $uniqID));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Episode entity.');
        }

        return ['episode' => $entity];
    }

    /**
     * Displays a form to edit an existing Episode entity.
     *
     * @Route("/{episode}/edit", name="oktolab_episode_edit")
     * @ParamConverter("episode", class="OktolabMediaBundle:Episode")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Request $request, $episode)
    {
        $entity = $episode;

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
    * Creates a form to edit a Episode entity.
    *
    * @param Episode $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Episode $entity)
    {
        $form = $this->createForm(
            new EpisodeType(),
            $entity,
            [
                'action' => $this->generateUrl(
                    'oktolab_episode_update',
                    ['id' => $entity->getId()]
                ),
                'method' => 'POST'
            ]
        );

        $form->add(
            'submit',
            'submit',
            ['label' => 'oktolab_media.edit_episode_button']
        );

        return $form;
    }
    /**
     * Edits an existing Episode entity.
     *
     * @Route("/{id}", name="oktolab_episode_update")
     * @Method("POST")
     * @Template("OktolabMediaBundle:Episode:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('OktolabMediaBundle:Episode')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Episode entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'oktolab_media.success_edit_episode'
            );

            return $this->redirect(
                $this->generateUrl(
                    'oktolab_episode_show',
                    ['uniqID' => $entity->getUniqID()]
                )
            );

        } else {
            $this->get('session')->getFlashBag()->add(
                'error',
                'oktolab_media.error_edit_episode'
            );
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Episode entity.
     *
     * @Route("/{id}", name="oktolab_episode_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('OktolabMediaBundle:Episode')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Episode entity.');
            }

            $this->get('oktolab_media_helper')->deleteEpisode($episode);
        }

        return $this->redirect($this->generateUrl('oktolab_episode'));
    }

    /**
     * Creates a form to delete a Episode entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('oktolab_episode_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add(
                'submit',
                'submit',
                [
                    'label' => 'oktolab_media.delete_episode_button',
                    'attr' => ['class' => 'btn btn-danger']
                ]
            )
            ->getForm()
        ;
    }

    /**
     * @Route("/{uniqID}/encode", name="oktolab_episode_encode")
     * @Method({"GET","POST"})
     * @Template()
     */
    public function encodeVideoAction(Request $request, $uniqID)
    {
        $em = $this->getDoctrine()->getManager();
        $episode = $em->getRepository(
            $this->container->getParameter('oktolab_media.episode_class')
            )->findOneBy(array('uniqID' => $uniqID));

        $this->get('oktolab_media')->addEncodeEpisodeJob(
            $episode->getUniqID(),
            $request->query->get('queue', false),
            $request->query->get('first', false)
        );
        $this->get('session')->getFlashBag()->add(
            'info',
            'oktolab_media.episode_encode_info'
        );

        return $this->redirect($request->headers->get('referer'));
    }

    /**
    * @Route("/{uniqID}/export", name="oktolab_media_export_episode")
    * @Method("GET")
    * @Template()
    * @Security("has_role('ROLE_OKTOLAB_MEDIA_READ')")
    */
    public function exportAction(Request $request, $uniqID)
    {
        $send = $request->query->get('keychain', false);
        if ($send) { // clicked send to remote application
            $keychain = $this->get('bprs_applink')->getKeychain($send);
            $success = $this->get('oktolab_keychain')->exportEpisode(
                $keychain,
                $uniqID,
                $request->query->get('overwrite', false)
            );

            if ($success) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    'oktolab_media.success_export_episode'
                );

                return $this->redirect(
                    $this->generateUrl(
                        'oktolab_media_export_episode',
                        ['uniqID' => $uniqID]
                    )
                );
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    'oktolab_media.error_export_episode'
                );
            }
        }

        $keychains = $this->get('bprs_applink')
            ->getKeychainsWithRole(MediaService::ROLE_WRITE);
        $episode = $this->get('oktolab_media')->getEpisode($uniqID);
        return ['episode' => $episode, 'keychains' => $keychains];
    }

    /**
     * Browse Episodes of an remote application (with the keychain)
     * @Route("/remote/{keychain}", name="oktolab_media_remote_episodes")
     * @Method("GET")
     * @Template()
     */
    public function listRemoteEpisodes(Keychain $keychain)
    {
        $episodes_url = $this->get('bprs_applink')->getApiUrlsForKey(
            $keychain,
            'oktolab_media_api_list_episodes'
        );

        if ($episodes_url) {
            $client = new Client();
            $response = $client->request(
                'GET',
                $episodes_url,
                ['auth' => [$keychain->getUser(), $keychain->getApiKey()]]
            );

            if ($response->getStatusCode() == 200) {
                $info = json_decode(
                    html_entity_decode((string)$response->getBody()), true
                );

                return ['result' => $info, 'keychain' => $keychain];
            }
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/reimport/{uniqID}", name="oktolab_media_reimport_episode")
     * @Method("GET")
     */
    public function reimportEpisodeAction(Request $request, $uniqID)
    {
        $oktolab_media = $this->get('oktolab_media');
        $episode = $oktolab_media->getEpisode($uniqID);
        $this->get('oktolab_media')->addEpisodeJob(
            $episode->getKeychain(),
            $uniqID,
            $request->query->get('overwrite', false)
        );
        $this->get('session')->getFlashBag()->add(
            'success',
            'oktolab_media.success_reimport_episode'
        );

        return $this->redirect(
            $this->generateUrl(
                'oktolab_episode_show',
                ['uniqID' => $uniqID]
            )
        );
    }

    /**
     * @Route("/import/{keychain}", name="oktolab_media_import_remote_episode")
     * @Method("GET")
     */
    public function importEpisodeAction(Request $request, Keychain $keychain)
    {
        $uniqID = $request->query->get('uniqID');
        if ($uniqID) {
            $this->get('oktolab_media')->addEpisodeJob(
                $keychain,
                $uniqID,
                $request->query->get('overwrite', false)
            );
            return new Response('', Response::HTTP_ACCEPTED);
        }
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/{uniqID}/push", name="oktolab_media_episode_push")
     * @Template()
     * @Method("GET")
     */
    public function pushAction($uniqID)
    {
        $keychains = $this->get('bprs_applink')
            ->getKeychainsWithRole(MediaService::ROLE_WRITE);
        return ['keychains' => $keychains];
    }
}
