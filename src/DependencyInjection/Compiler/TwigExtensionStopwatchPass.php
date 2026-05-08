<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\DependencyInjection\Compiler;

use Bill7189\TwigInitProfiler\Profiler\TwigExtensionProfiler;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigExtensionStopwatchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('debug.stopwatch') || !$container->has(TwigExtensionProfiler::class)) {
            return;
        }

        $excludedClasses = $this->paramAsList($container, 'bill7189_twig_init_profiler.excluded_classes');
        $excludedPrefixes = $this->paramAsList($container, 'bill7189_twig_init_profiler.excluded_prefixes');

        $extensionIds = [];

        foreach ($container->findTaggedServiceIds('twig.extension') as $id => $_tags) {
            $definition = $container->getDefinition($id);

            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            $class = $definition->getClass();
            if (!$class) {
                continue;
            }

            if (in_array($class, $excludedClasses, true)) {
                continue;
            }

            foreach ($excludedPrefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($class, $prefix)) {
                    continue 2;
                }
            }

            $innerId = $id . '.stopwatch_inner';
            $innerDef = clone $definition;
            $innerDef->clearTags();
            $innerDef->setPublic(false);
            $container->setDefinition($innerId, $innerDef);

            $definition->setArguments([
                new ServiceClosureArgument(new Reference($innerId)),
                $class,
            ]);
            $definition->setFactory([new Reference(TwigExtensionProfiler::class), 'profile']);
            $definition->setMethodCalls([]);
            $definition->setConfigurator(null);
            $definition->setProperties([]);

            $extensionIds[$id] = $class;
        }

        $container->setParameter('bill7189_twig_init_profiler.profiled_extensions', $extensionIds);
    }

    /**
     * @return list<string>
     */
    private function paramAsList(ContainerBuilder $container, string $name): array
    {
        if (!$container->hasParameter($name)) {
            return [];
        }

        $value = $container->getParameter($name);

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
