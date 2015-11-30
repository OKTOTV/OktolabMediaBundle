<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oktolab\MediaBundle\Entity\Series;
use Oktolab\MediaBundle\Form\SeriesType;

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
        $dql = "SELECT s FROM OktolabMediaBundle:Series s";
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
     * Creates a new Series entity.
     *
     * @Route("/new", name="oktolab_series_create")
     * @Method("POST")
     * @Template("OktolabMediaBundle:Series:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Series();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oktolab_series_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Series entity.
     *
     * @param Series $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Series $entity)
    {
        $form = $this->createForm(new SeriesType(), $entity, array(
            'action' => $this->generateUrl('oktolab_series_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Series entity.
     *
     * @Route("/new", name="series_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Series();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Series entity.
     *
     * @Route("/show/{id}/{page}", name="oktolab_series_show", defaults={"page" = 1}, requirements={"page": "\d+"})
     * @Method("GET")
     * @Template()
     */
    public function showAction($id, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $series = $em->getRepository($this->container->getParameter('oktolab_media.series_class'))->find($id);
        $dql = "SELECT e FROM ".$this->container->getParameter('oktolab_media.episode_class')." e WHERE e.series =".$id;
        $query = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $page/*page number*/,
            3
        );

        if (!$series) {
            throw $this->createNotFoundException('Unable to find Series entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'series'      => $series,
            'pagination' => $pagination,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Series entity.
     *
     * @Route("/{id}/edit", name="oktolab_series_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->container->getParameter('oktolab_media.series_class'))->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Series entity.');
        }

        $editForm = $this->createEditForm($entity);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Series entity.
    *
    * @param Series $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Series $entity)
    {
        $form = $this->createForm(new SeriesType(), $entity, array(
            'action' => $this->generateUrl('oktolab_series_update', array('id' => $entity->getId())),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Series entity.
     *
     * @Route("/{id}/edit", name="oktolab_series_update")
     * @Method("POST")
     * @Template("OktolabMediaBundle:Series:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('OktolabMediaBundle:Series')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Series entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_edit_series');
            return $this->redirect($this->generateUrl('oktolab_series_show', array('id' => $id)));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_edit_series');
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Series entity.
     *
     * @Route("/{id}", name="oktolab_series_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('OktolabMediaBundle:Series')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Series entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('series'));
    }

    /**
     * Creates a form to delete a Series entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('oktolab_series_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
