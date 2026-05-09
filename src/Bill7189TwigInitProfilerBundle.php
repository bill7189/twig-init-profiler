<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler;

use Bill7189\TwigInitProfiler\DependencyInjection\Compiler\TwigExtensionStopwatchPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Bill7189TwigInitProfilerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigExtensionStopwatchPass());
    }
}
