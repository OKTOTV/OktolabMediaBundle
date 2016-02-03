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
        $container->setParameter('oktolab_media.series_class', $config['series_class']);
        $container->setParameter('oktolab_media.asset_class', $config['asset_class']);
        $container->setParameter('oktolab_media.resolutions', $config['resolutions']);
        $container->setParameter('oktolab_media.keep_original', $config['keep_original']);
    }
}
