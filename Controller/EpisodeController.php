<?php

namespace Oktolab\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

            $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_create_episode');
            return $this->redirect($this->generateUrl('oktolab_episode_show', array('uniqID' => $entity->getUniqID())));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_create_episode');
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

        $form->add('submit', 'submit', ['label' => 'oktolab_media.new_episode_create_button', 'attr' => ['class' => 'btn btn-primary']]);

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

        return array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
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
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'oktolab_media.edit_episode_button'));

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

            $this->get('session')->getFlashBag()->add('success', 'oktolab_media.success_edit_episode');
            return $this->redirect($this->generateUrl('oktolab_episode_show', array('uniqID' => $entity->getUniqID())));
        } else {
            $this->get('session')->getFlashBag()->add('error', 'oktolab_media.error_edit_episode');
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
            ->add('submit', 'submit', array('label' => 'oktolab_media.delete_episode_button', 'attr' => ['class' => 'btn btn-danger']))
            ->getForm()
        ;
    }
}
