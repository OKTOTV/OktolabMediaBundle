<?php

namespace Oktolab\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oktolab_media');
        $rootNode
            ->children()
                ->arrayNode('origin')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')->defaultValue('http://www.oktolab.at')->end()
                        ->scalarNode('position')->defaultValue('top-right')->end()
                        ->integerNode('margin')->defaultValue(8)->end()
                        ->scalarNode('logo')->end()
                    ->end()
                ->end()
                ->scalarNode('player_type')->defaultValue('jwplayer')->end()
                ->scalarNode('player_url')->isRequired()->end()
                ->scalarNode('episode_class')->isRequired()->end()
                ->scalarNode('series_class')->isRequired()->end()
                ->scalarNode('media_class')->defaultValue('Oktolab\MediaBundle\Entity\Media')->end()
                ->scalarNode('asset_class')->isRequired()->end()
                ->booleanNode('keep_original')->defaultFalse()->end()
                ->scalarNode('encoding_filesystem')->defaultValue('cache')->end()
                ->scalarNode('posterframe_filesystem')->defaultValue('posterframe')->end()
                ->scalarNode('default_filesystem')->defaultValue('video')->end()
                ->scalarNode('serializing_schema')->end()
                ->scalarNode('worker_queue')->defaultValue('oktolab_media')->end()
                ->arrayNode('resolutions')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->integerNode('sortNumber')->defaultValue(1)->end()
                            ->scalarNode('video_codec')->defaultValue("h264")->end()
                            ->scalarNode('video_framerate')->defaultValue("50/1")->end()
                            ->scalarNode('video_width')->end()
                            ->scalarNode('video_height')->end()
                            ->integerNode('video_bitrate')->defaultValue(5000000)->end() // 5 mbit
                            ->integerNode('crf_rate')->defaultValue(23)->end()
                            ->scalarNode('preset')->defaultValue('veryslow')->end()
                            ->scalarNode('audio_codec')->defaultValue("aac")->end()
                            ->scalarNode('audio_sample_rate')->defaultValue("48000")->end()
                            ->scalarNode('container')->defaultValue('mov')->end()
                            ->booleanNode('public')->defaultTrue()->end()
                            ->scalarNode('adapter')->defaultValue('video')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('api_urls')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
            ->end();
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
