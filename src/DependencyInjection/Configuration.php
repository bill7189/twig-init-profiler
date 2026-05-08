<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bill7189_twig_init_profiler');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('excluded_classes')
                    ->info('Exact Twig extension FQCNs to skip when profiling.')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('excluded_prefixes')
                    ->info('Namespace prefixes to skip when profiling (matched with str_starts_with).')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
