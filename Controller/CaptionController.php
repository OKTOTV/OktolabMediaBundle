<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Oktolab\MediaBundle\Entity\Caption;
use Oktolab\MediaBundle\Form\CaptionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Caption controller.
 *
 * @Route("/oktolab_media/caption")
 */
class CaptionController extends Controller
{
    /**
     * @Route("/", name="oktolab_caption_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $results = $request->query->get('results', 10);
        $repo = $this->getDoctrine()->getManager()
            ->getRepository('OktolabMediaBundle:Caption');
        $query = $repo->getAll(true);
        $paginator = $this->get('knp_paginator');
        $captions = $paginator->paginate($query, $page, $results);

        return ['captions' => $captions];
    }

    /**
     * @Route("/show/{caption}", name="oktolab_caption_show")
     * @Template()
     */
    public function showAction(Caption $caption)
    {
        return ['caption' => $caption];
    }

    /**
     * @Route("/edit/{caption}", name="oktolab_caption_edit")
     * @Template()
     */
    public function editAction(Request $request, Caption  $caption)
    {
        $form = $this->createForm(CaptionType::class, $caption);
        $form->add(
            'delete',
            SubmitType::class,
            [
                'label' => 'oktolab_media.delete_caption_button',
                'attr' => ['class' => 'btn btn-link']
            ]
        );
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'oktolab_media.update_caption_button',
                'attr' => ['class' => 'btn btn-primary']
            ]
        );

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($form->get('submit')->isClicked()) {
                    $em->persist($caption);
                    $em->flush();
                    $this
                        ->get('session')
                        ->getFlashBag()
                        ->add('success', 'oktolab_media.success_update_caption');
                    return $this->redirect(
                        $this->generateUrl(
                            'oktolab_caption_show',
                            ['caption' => $caption->getId()]
                            )
                        );
                } else {
                    $uniqID = $caption->getEpisode()->getUniqd();
                    $em->remove($caption);
                    $em->flush();
                    $this
                        ->get('session')
                        ->getFlashBag()
                        ->add('success', 'oktolab_media.success_delete_caption');
                    return $this->redirect(
                        $this->generateUrl(
                            'oktolab_episode_show',
                            ['uniqID' => $uniqID]
                            )
                        );
                }
            }
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_edit_caption');
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/new/{uniqID}", name="oktolab_caption_new")
     * @Template()
     */
    public function newAction(Request $request, $uniqID)
    {
        $caption = new Caption();
        $form = $this->createForm(CaptionType::class, $caption);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'oktolab_media.update_caption_button',
                'attr' => ['class' => 'btn btn-primary']
            ]
        );

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $episode = $this->get('oktolab_media')->getEpisode($uniqID);
                $episode->addCaption($caption);
                $em = $this->getDoctrine()->getManager();
                $em->persist($episode);
                $em->persist($caption);
                $em->flush();
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', 'oktolab_media.success_create_caption');
                return $this->redirect(
                    $this->generateUrl(
                        'oktolab_caption_show',
                        ['caption' => $caption->getId()]
                        )
                    );
            }
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_edit_caption');
        }

        return ['form' => $form->createView()];
    }
}
