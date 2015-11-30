<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SeriesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', 'text',
            ['label' => 'oktolab_media.series_name_label']
        )

        ->add('webtitle', 'text',
            ['label' => 'oktolab_media.series_webtitle_label']
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
            ['label' => 'oktolab_media.series_isActive_label']
        )
        ->add('uniqID', 'text',
            ['label' => 'oktolab_media.series_uniqID_label']
        )
        ->add('posterframe', 'asset',
            ['label' => 'oktolab_media.series_posterframe_label']
        )
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Series'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'oktolab_mediabundle_series';
    }
}
