<?php

namespace Oktolab\MediaBundle;

final class OktolabMediaEvent
{
    /**
     * TODO: throw event
     * The oktolab_media.encoded_episode event is thrown each time after all episode medias are encoded.
     *
     * The event listener receives an
     * Oktolab\MediaBundle\Entity\Episode instance. The actual entity depends on the oktolab_media.episode_class setting
     *
     * @var string
     */
    const ENCODED_EPISODE = 'oktolab_media.encoded_episode';

    /**
     * The oktolab_media.imported_episode_metadata event is thrown each time the metadata of an episode was imported.
     * the event listener receives the uniqID of the imported episode.
     */
    const IMPORTED_EPISODE_METADATA = 'oktolab_media.imported_episode_metadata';

    /**
     * the oktolab_media.imported_episode_posterframe event is thrown each time the posterframe of an episode was imported.
     * the event listener receives the uniqID of the imported episode.
     */
    const IMPORTED_EPISODE_POSTERFRAME = 'oktolab_media.imported_episode_posterframe';

    /**
     * the oktolab_media.created_episode event is fired each time a new episode was persisted in the database.
     * See the EpisodeLifecycleListener for more info
     */
    const CREATED_EPISODE = 'oktolab_media.created_episode';

    /**
     * Is thrown each time an episode is finalized.
     */
    const FINALIZED_EPISODE = 'oktolab_media.finalized_episode';

    /**
     * The oktolab_media.imported_series_metadata event is thrown each time the metadata of an series was imported.
     * the event listener receives the uniqID of the imported series.
     */
    const IMPORTED_SERIES_METADATA = 'oktolab_media.imported_series_metadata';

    /**
    * TODO: throw event
    * The oktolab_media.delete_episode event is thrown each time before an episode gets deleted.
    */
    const DELETE_EPISODE = 'oktolab_media.delete_episode';

    /**
    * The oktolab_media.delete_series event is thrown each time a series gets deleted.
    * The event listener receives an
    * Oktolab\MediaBundle\Entity\Series instance. The actual entity depends on the oktolab_media.series_class setting
    */
    const DELETE_SERIES =   'oktolab_media.delete_series';
}
