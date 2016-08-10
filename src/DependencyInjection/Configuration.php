<?php
namespace Vanio\WebBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        /* @noinspection PhpUndefinedMethodInspection */
        $treeBuilder->root('vanio_web')
            ->children()
                ->scalarNode('referer_fallback_path')->defaultValue('/')->end()
            ->end();

        return $treeBuilder;
    }
}
