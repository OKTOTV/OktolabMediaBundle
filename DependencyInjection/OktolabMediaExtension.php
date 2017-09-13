<?php

namespace Oktolab\MediaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OktolabMediaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('oktolab_media.episode_class', $config['episode_class']);
        $container->setParameter('oktolab_media.media_class', $config['media_class']);
        $container->setParameter('oktolab_media.series_class', $config['series_class']);
        $container->setParameter('oktolab_media.asset_class', $config['asset_class']);
        $container->setParameter('oktolab_media.resolutions', $config['resolutions']);
        $container->setParameter('oktolab_media.keep_original', $config['keep_original']);
        $container->setParameter('oktolab_media.encoding_filesystem', $config['encoding_filesystem']);
        $container->setParameter('oktolab_media.player_type', $config['player_type']);
        $container->setParameter('oktolab_media.player_url', $config['player_url']);
        $container->setParameter('oktolab_media.origin', $config['origin']);
        $container->setParameter('oktolab_media.posterframe_filesystem', $config['posterframe_filesystem']);
        $container->setParameter('oktolab_media.sprite_filesystem', $config['sprite_filesystem']);
        $container->setParameter('oktolab_media.default_filesystem', $config['default_filesystem']);
        $container->setParameter('oktolab_media.serializing_schema', $config['serializing_schema']);
        $container->setParameter('oktolab_media.worker_queue', $config['worker_queue']);
        $container->setParameter('oktolab_media.sprite_worker_queue', $config['sprite_worker_queue']);

        $container->setParameter('oktolab_media.sprite_width', $config['sprite_width']);
        $container->setParameter('oktolab_media.sprite_height', $config['sprite_height']);
        $container->setParameter('oktolab_media.sprite_interval', $config['sprite_interval']);

        $this->addApiUrlParameter($container, $config['api_urls']);
    }

    private function addApiUrlParameter(ContainerBuilder $container, array $urls)
    {
        $default_urls = [
            'oktolab_media_api_list_series',
            'oktolab_media_api_show_series',
            'oktolab_media_api_list_episodes',
            'oktolab_media_api_show_episode',
            'oktolab_media_api_show_asset',
            'oktolab_media_api_import_series',
            'oktolab_media_api_import_episode',
            'oktolab_media_embed_episode',
            'oktolab_media_caption_for_episode',
            'oktolab_media_origin_for_episode'
        ];

        $urls = array_merge($default_urls, $urls);
        $container->setParameter('oktolab_media.api_urls', $urls);
    }
}
