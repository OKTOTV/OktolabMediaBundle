<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oktolab\MediaBundle\Entity\Episode;
use Oktolab\MediaBundle\Form\EpisodeType;

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
        $entity = new Episode();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oktolab_episode_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
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
        $form = $this->createForm(new EpisodeType(), $entity, array(
            'action' => $this->generateUrl('oktolab_episode_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Episode entity.
     *
     * @Route("/new", name="oktolab_episode_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Episode();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
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
        $entity = $em->getRepository($this->container->getParameter('oktolab_media.episode_class'))->findOneBy(array('uniqID' => $uniqID));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Episode entity.');
        }

        return ['episode' => $entity];
    }

    /**
     * Displays a form to edit an existing Episode entity.
     *
     * @Route("/{id}/edit", name="oktolab_episode_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->container->getParameter('oktolab_media.episode_class'))->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Episode entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
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
        $form = $this->createForm(new EpisodeType(), $entity, array(
            'action' => $this->generateUrl('oktolab_episode_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Episode entity.
     *
     * @Route("/{id}", name="oktolab_episode_update")
     * @Method("PUT")
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

            return $this->redirect($this->generateUrl('oktolab_episode_edit', array('id' => $id)));
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

            $em->remove($entity);
            $em->flush();
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
            ->setAction($this->generateUrl('oktolab_episode_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
