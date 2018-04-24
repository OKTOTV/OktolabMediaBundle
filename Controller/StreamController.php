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
use Oktolab\MediaBundle\Form\StreamType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Stream controller.
 *
 * @Route("/oktolab_media/stream")
 * @Security("has_role('ROLE_OKTOLAB_MEDIA_STREAM_READ')")
 */
class StreamController extends Controller
{
    /**
     * Creates a new Episode entity.
     *
     * @Route("/", name="oktolab_media_stream_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $stream = $this->get('oktolab_media_stream')->createStream();
        $form = $this->createForm(StreamType::class, $stream);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'oktolab_media.new_stream_button',
                'attr' => ['class' => 'btn btn-primary']
            ]
        );

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($stream);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'oktolab_media.success_create_stream'
                );

                return $this->redirect(
                    $this->generateUrl(
                        'oktolab_media_stream_show',
                        ['uniqID' => $stream->getUniqID()]
                    )
                );
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    'oktolab_media.error_create_stream'
                );
            }
        }

        return ['form'   => $form->createView()];
    }

    /**
     * @Route("/index", name="oktolab_media_stream_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $results = $request->query->get('results', 10);
        $em = $this->getDoctrine()->getManager();
        $class = $this->container->getParameter('oktolab_media.stream_class');
        $query = $em->getRepository($class)->findAllForClass($class, true);
        $paginator = $this->get('knp_paginator');
        $streams = $paginator->paginate($query, $page, $results);

        return ['streams' => $streams];
    }

    /**
     * Finds and displays a Episode entity.
     *
     * @Route("/show/{uniqID}", name="oktolab_media_stream_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($uniqID)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $this->get('oktolab_media_stream')->getStream($uniqID);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Stream entity.');
        }

        return ['stream' => $entity];
    }

    /**
     * Displays a form to edit an existing Episode entity.
     *
     * @Route("/edit/{uniqID}", name="oktolab_media_stream_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Request $request, $uniqID)
    {
        $stream = $this->get('oktolab_media_stream')->getStream($uniqID);
        $form = $this->createForm(StreamType::class, $stream);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'oktolab_media.edit_stream_button',
                'attr' => ['class' => 'btn btn-primary']
            ]
        );
        $form->add(
            'delete',
            SubmitType::class,
            [
                'label' => 'oktolab_media.delete_stream_button',
                'attr' => ['class' => 'btn btn-link']
            ]
        );

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($form->get('submit')->isClicked()) {
                    $em->persist($stream);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'oktolab_media.success_edit_stream'
                    );

                    return $this->redirect(
                        $this->generateUrl(
                            'oktolab_media_stream_show',
                            ['uniqID' => $stream->getUniqID()]
                        )
                    );
                } elseif ($form->get('delete')->isClicked()) {
                    $em->remove(stream);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        'oktolab_media.success_delete_stream'
                    );
                    return $this->redirect(
                        $this->generateUrl(
                            'oktolab_media_stream_index'
                        )
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    'oktolab_media.error_edit_stream'
                );
            }
        }

        return ['form'   => $form->createView()];
    }

    /**
     * @Route("/record", name="oktolab_media_stream_record")
     * @Method("GET")
     */
    public function recordAction(Request $request)
    {
        $stream_service = $this->get('oktolab_media_stream');
        $stream = $stream_service->getStream($request->query->get('uniqID'));
        if ($request->query->get('start', 1)) {
            $success = $stream_service->startRecording($stream);
        } else {
            $success = $stream_service->endRecording($stream);
        }

        if ($success) {
            return new Response('', Response::HTTP_OK);
        }
        return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * @Route("/redirect", name="oktolab_media_stream_redirect")
     * @Method("GET")
     */
    public function redirectAction(Request $request)
    {
        $stream_service = $this->get('oktolab_media_stream');
        $stream = $stream_service->getStream($request->query->get('uniqID'));
        $success = $stream_service->redirect(
            $stream->getRtmpApp(),
            $stream->getUniqID(),
            $request->query->get('adress'),
            $request->query->get('clientid'),
            $request->query->get('new_name'),
            $request->query->get('type', 'publish'),
            $request->query->get('srv', null)
        );

        if ($success) {
            return new Response('', Response::HTTP_OK);
        }
        return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * @Route("/drop", name="oktolab_media_stream_drop")
     * @Method("GET")
     */
    public function dropAction(Request $request)
    {
        $stream_service = $this->get('oktolab_media_stream');
        $stream = $stream_service->getStream($request->query->get('uniqID'));
        $success = $stream_service->drop(
            $stream->getRtmpApp(),
            $stream->getUniqID(),
            $request->query->get('type', 'publish'),
            $request->query->get('adress', null),
            $request->query->get('clientid', null),
            $request->query->get('srv', null)
        );

        if ($success) {
            return new Response('', Response::HTTP_OK);
        }
        return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
