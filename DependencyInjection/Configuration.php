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
            ->end();

        return $treeBuilder;
    }
}
