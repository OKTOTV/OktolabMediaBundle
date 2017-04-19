<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Bprs\AssetBundle\Form\Type\AssetType;

class SeriesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('name', TextType::class,
            ['label' => 'oktolab_media.series_name_label']
        )

        ->add('webtitle', TextType::class,
            ['label' => 'oktolab_media.series_webtitle_label']
        )

        ->add('description', TextareaType::class,
            [
                'label' => 'oktolab_media.description_label',
                'attr' => [
                    'class' => 'character500', 'style' => 'height:200px',
                    'placeholder' => 'oktolab_media.description_placeholder'
                ]
            ]
        )

        ->add('isActive', CheckboxType::class,
            [
                'label' => 'oktolab_media.series_isActive_label',
                'required' => false
            ]
        )

        ->add('uniqID', TextType::class,
            ['label' => 'oktolab_media.series_uniqID_label']
        )

        ->add('posterframe', AssetType::class,
            ['label' => 'oktolab_media.series_posterframe_label']
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Series'
        ));
    }

    public function getBlockPrefix()
    {
        return 'oktolab_mediabundle_series';
    }
}
