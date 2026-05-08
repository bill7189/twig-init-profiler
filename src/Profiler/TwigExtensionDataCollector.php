<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\Profiler;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwigExtensionDataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly TwigExtensionProfiler $profiler,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $results = $this->profiler->getResults();
        arsort($results);

        $totalMs = array_sum($results);

        $this->data = [
            'extensions' => $results,
            'total_ms' => $totalMs,
            'count' => count($results),
        ];
    }

    public static function getTemplate(): ?string
    {
        return '@Bill7189TwigInitProfiler/Profiler/twig_extensions.html.twig';
    }

    /** @return array<string, float> */
    public function getExtensions(): array
    {
        return $this->data['extensions'] ?? [];
    }

    public function getTotalMs(): float
    {
        return $this->data['total_ms'] ?? 0.0;
    }

    public function getCount(): int
    {
        return $this->data['count'] ?? 0;
    }
}
