<?php

namespace Oktolab\MediaBundle;

final class OktolabMediaEvent
{
    /**
     * The oktolab_media.encoded_episode event is thrown each time after all episode medias are encoded.
     *
     * The event listener receives an
     * Oktolab\MediaBundle\Entity\Episode instance. The actual entity depends on the oktolab_media.episode_class setting
     *
     * @var string
     */
    const ENCODED_EPISODE = 'oktolab_media.encoded_episode';

    /**
    * The oktolab_media.delete_series event is thrown each time a series gets deleted.
    * The event listener receives an
    * Oktolab\MediaBundle\Entity\Series instance. The actual entity depends on the oktolab_media.series_class setting
    */
    const DELETE_SERIES =   'oktolab_media.delete_series';
}
