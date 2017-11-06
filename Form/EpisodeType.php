<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Bprs\AssetBundle\Form\Type\AssetType;
use Oktolab\Media\Entity\Episode;

class EpisodeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('stereomode', ChoiceType::class,
                [
                    'choices' => [
                        Episode::STEREOMODE_NONE => "oktolab_media.stereomode_none",
                        Episode::STEREOMODE_MONOSCOPIC => "oktolab_media.stereomode_monoscopic",
                        Episode::STEREOMODE_TOPBOTTOM => "oktolab_media.stereomode_topbottom",
                        Episode::STEREOMODE_LEFTRIGHT => "oktolab_media.stereomode_leftright"
                    ],
                    'label' => 'oktolab_media.stereomode_label'
                ]
            )

            ->add('agerating', IntegerType::class,
                [
                    'label' => 'oktolab_media.agerating_label'
                ]
            )

            ->add('name', TextType::class,
                [
                    'label' => 'oktolab_media.name_label',
                    'required' => false,
                ]
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
                    'label' => 'oktolab_media.isActive_label',
                    'required' => false
                ]
            )
            ->add('onlineStart', DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'label' => 'oktolab_media.onlineStart_label',
                    'attr' => ['placeholder' => 'oktolab_media.onlineStart_placeholder']
                ]
            )
            ->add('onlineEnd', DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'placeholder' => 'oktolab_media.onlineEnd_placeholder',
                    'label' => 'oktolab_media.onlineEnd_label',
                    'attr' => ['placeholder' => 'oktolab_media.onlineEnd_placeholder']
                ]
            )

            ->add('firstRanAt', DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'placeholder' => 'oktolab_media.firstRanAt_placeholder',
                    'label' => 'oktolab_media.firstRanAt_label',
                    'attr' => ['placeholder' => 'oktolab_media.firstRanAt_placeholder']
                ]
            )

            ->add('uniqID', TextType::class,
                ['label' => 'oktolab_media.uniqID_label']
            )

            ->add('posterframe', AssetType::class,
                ['label' => 'oktolab_media.posterframe_label']
            )

            ->add('video', AssetType::class, ['label' => 'oktolab_media.video_label'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Episode'
        ));
    }

    public function getBlockPrefix()
    {
        return 'oktolab_mediabundle_episode';
    }
}
