<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Bprs\AssetBundle\Form\Type\AssetType;

class MediaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quality', TextType::class, ['label' => 'oktolab_media_quality_label'])
            ->add('public', CheckboxType::class, ['required' => false, 'label' => 'oktolab_media_public_label'])
            ->add('sortNumber', IntegerType::class, ['label' => 'oktolab_media_sortNumber_label'])
            ->add('asset', AssetType::class, ['label' => 'oktolab_media_asset_label']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oktolab\MediaBundle\Entity\Media'
        ]);
    }

    public function getName()
    {
        return 'oktolab_media_mediatype';
    }
}
