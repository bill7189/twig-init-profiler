<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\Profiler;

use Twig\Extension\AbstractExtension;

class TwigExtensionProfiler
{
    /** @var array<string, float> */
    private array $results = [];

    public function profile(\Closure $factory, string $class): AbstractExtension
    {
        $shortName = substr(strrchr($class, '\\') ?: $class, 1);
        $start = hrtime(true);
        $extension = $factory();
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $this->results[$shortName] = $durationMs;

        return $extension;
    }

    /**
     * @return array<string, float> Extension short name => duration in ms
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
