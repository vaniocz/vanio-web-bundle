<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        $treeBuilder->root('vanio_web')
            ->children()
                ->booleanNode('detect_request_type')->defaultFalse()->end()
                ->scalarNode('referer_parameter')->defaultValue('_referer')->end()
                ->scalarNode('referer_fallback_path')->defaultValue('/')->end()
                ->booleanNode('render_snippets')->defaultFalse()->end()
                ->booleanNode('recursive_form_label')->defaultFalse()->end()
                ->booleanNode('collection_widget')->defaultFalse()->end()
                ->scalarNode('google_maps_api_key')->defaultNull()->end()
                ->booleanNode('multilingual')->defaultFalse()->end()
                ->arrayNode('multilingual_root_paths')
                    ->defaultValue(['/'])
                    ->beforeNormalization()
                        ->ifTrue(function ($value) {
                            return !is_array($value);
                        })
                        ->then(function ($value) {
                            return [$value];
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('multilingual_supported_locales')
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
