<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Oktolab\MediaBundle\Entity\Episode;

class StreamType extends AbstractType
{
    private $choices;

    public function __construct($streamserver_config)
    {
        foreach ($streamserver_config as $key => $value) {
            $this->choices[] = [$key => $key];
        }
    }

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
                        "oktolab_media.stereomode_none" => Episode::STEREOMODE_NONE,
                        "oktolab_media.stereomode_monoscopic" => Episode::STEREOMODE_MONOSCOPIC,
                        "oktolab_media.stereomode_topbottom" => Episode::STEREOMODE_TOPBOTTOM,
                        "oktolab_media.stereomode_leftright" => Episode::STEREOMODE_LEFTRIGHT
                    ],
                    'label' => 'oktolab_media.stereomode_label'
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
            ->add('rtmp_server', ChoiceType::class,
                [
                    'choices' => $this->choices,
                    'label' => 'oktolab_media.stream_rtmp_server_label'
                ])
            ->add('technical_status', TextType::class,
                [
                    'label' => 'oktolab_media.technical_status_label',
                    'required' => false,
                ]
            )

            ->add('uniqID', TextType::class,
                ['label' => 'oktolab_media.uniqID_label']
            )

            // ->add('posterframe', AssetType::class,
            //     ['label' => 'oktolab_media.posterframe_label']
            // )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Stream'
        ));
    }

    public function getBlockPrefix()
    {
        return 'oktolab_mediabundle_stream';
    }
}
