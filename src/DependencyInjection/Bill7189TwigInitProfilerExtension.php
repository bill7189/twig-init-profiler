<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class Bill7189TwigInitProfilerExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter(
            'bill7189_twig_init_profiler.excluded_classes',
            array_values($config['excluded_classes'] ?? []),
        );
        $container->setParameter(
            'bill7189_twig_init_profiler.excluded_prefixes',
            array_values($config['excluded_prefixes'] ?? []),
        );

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../Resources/config'),
        );
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'bill7189_twig_init_profiler';
    }
}
