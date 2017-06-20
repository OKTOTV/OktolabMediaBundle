<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Oktolab\MediaBundle\Entity\Caption;


class CaptionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class,
                [
                    'label' => 'oktolab_media.caption_name_label',
                    'attr'  =>  [
                        'placeholder' => 'oktolab_media.caption_label_placeholder'
                    ]
                ]
            )
            ->add('content', TextareaType::class,
                [
                    'label' => 'oktolab_media.caption_content_label',
                    'attr' => [
                        'placeholder' => 'oktolab_media.caption_content_placeholder',
                        'rows'        => '50'
                    ]
                ]
            )
            ->add('kind', ChoiceType::class,
                [
                    'label' => 'oktolab_media.caption_kind_label',
                    'choices' => [
                        Caption::OKTOLAB_CAPTIONKIND_SUB => Caption::OKTOLAB_CAPTIONKIND_SUB,
                        Caption::OKTOLAB_CAPTIONKIND_CAP => Caption::OKTOLAB_CAPTIONKIND_CAP,
                        Caption::OKTOLAB_CAPTIONKIND_CHAP => Caption::OKTOLAB_CAPTIONKIND_CHAP,
                        Caption::OKTOLAB_CAPTIONKIND_DESC => Caption::OKTOLAB_CAPTIONKIND_DESC,
                    ]
            ])
            ->add('public', CheckboxType::class, ['label' => 'oktolab_media_caption_public_label'])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Oktolab\MediaBundle\Entity\Caption'
        ));
    }

    public function getBlockPrefix()
    {
        return 'oktolab_mediabundle_caption';
    }
}
