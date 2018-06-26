<?php

namespace Oktolab\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Bprs\AssetBundle\Form\Type\AssetType;
use Oktolab\MediaBundle\Entity\Media;

class MediaType extends AbstractType
{
    private $trans;

    public function __construct($trans)
    {
        $this->trans = $trans;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'quality',
                TextType::class,
                [
                    'label' => 'oktolab_media_quality_label',
                    'attr' => [
                        'placeholder' => 'oktolab_media_media_quality_placeholder'
                    ]
                ]
            )
            ->add('status', ChoiceType::class, [
                'choices' => [
                    $this->trans->transchoice('oktolab_media.media_status_choice', Media::OKTOLAB_MEDIA_STATUS_MEDIA_TOPROGRESS) => Media::OKTOLAB_MEDIA_STATUS_MEDIA_TOPROGRESS,
                    $this->trans->transchoice('oktolab_media.media_status_choice', Media::OKTOLAB_MEDIA_STATUS_MEDIA_INPROGRESS) => Media::OKTOLAB_MEDIA_STATUS_MEDIA_INPROGRESS,
                    $this->trans->transchoice('oktolab_media.media_status_choice', Media::OKTOLAB_MEDIA_STATUS_MEDIA_FINISHED) => Media::OKTOLAB_MEDIA_STATUS_MEDIA_FINISHED
                ]
            ])
            ->add('public', CheckboxType::class, ['required' => false, 'label' => 'oktolab_media_public_label'])
            ->add('sortNumber', IntegerType::class, ['label' => 'oktolab_media_sortNumber_label'])
            ->add('progress', IntegerType::class, ['label' => 'oktolab_media_progress_label'])
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
