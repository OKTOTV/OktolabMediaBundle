<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EpisodeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text',
                ['label' => 'oktolab_media.name_label']
            )

            ->add('description', 'textarea',
                [
                    'label' => 'oktolab_media.description_label',
                    'attr' => [
                        'class' => 'character500', 'style' => 'height:200px',
                        'placeholder' => 'oktolab_media.description_placeholder'
                    ]
                ]
            )

            ->add('isActive', 'checkbox',
                ['label' => 'oktolab_media.isActive_label']
            )

            ->add('onlineStart', 'datetime',
                [
                    'widget' => 'single_text',
                    'html5' => false,
                    //'format' => 'd.m.Y H:i',
                    'label' => 'oktolab_media.onlineStart_label',
                    'attr' => ['placeholder' => 'oktolab_media.onlineStart_placeholder']
                ]
            )

            ->add('onlineEnd', 'datetime',
                [
                    'widget' => 'single_text',
                    'html5' => false,
                    //'format' => 'd.m.Y H:i',
                    'placeholder' => 'oktolab_media.onlineEnd_placeholder',
                    'label' => 'oktolab_media.onlineEnd_label',
                    'attr' => ['placeholder' => 'oktolab_media.onlineEnd_placeholder']
                ]
            )

            ->add('uniqID', 'text',
                ['label' => 'oktolab_media.uniqID_label']
            )

            ->add('posterframe', 'asset',
                ['label' => 'oktolab_media.posterframe_label']
            )

            ->add('video', 'asset', ['label' => 'oktolab_media.video_label'])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Episode'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'oktolab_mediabundle_episode';
    }
}
