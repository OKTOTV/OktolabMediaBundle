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
}
