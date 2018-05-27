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
                ->booleanNode('override_default_route_names')->defaultFalse()->end()
                ->booleanNode('detect_request_type')->defaultFalse()->end()
                ->scalarNode('referer_parameter')->defaultValue('_referer')->end()
                ->scalarNode('referer_fallback_path')->defaultValue('/')->end()
                ->scalarNode('target_path_parameter')->defaultValue('_target_path')->end()
                ->scalarNode('target_path_fallback')->defaultValue('/')->end()
                ->booleanNode('render_snippets')->defaultFalse()->end()
                ->booleanNode('recursive_form_label')->defaultFalse()->end()
                ->booleanNode('collection_widget')->defaultFalse()->end()
                ->booleanNode('form_choice_widget')->defaultFalse()->end()
                ->scalarNode('google_maps_api_key')->defaultNull()->end()
                ->booleanNode('multilingual')->defaultFalse()->end()
                ->arrayNode('multilingual_root_paths')
                    ->prototype('scalar')->end()
                    ->defaultValue(['/'])
                    ->beforeNormalization()
                        ->ifTrue(function ($value) {
                            return !is_array($value);
                        })
                        ->then(function ($value) {
                            return [$value];
                        })
                    ->end()
                ->end()
                ->arrayNode('multilingual_supported_locales')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('multilingual_locale_prefixes')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                    ->beforeNormalization()
                        ->ifTrue(function ($value) {
                            return !is_array($value);
                        })
                        ->then(function ($value) {
                            return [$value];
                        })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
