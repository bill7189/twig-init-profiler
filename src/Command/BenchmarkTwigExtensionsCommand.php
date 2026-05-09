<?php

declare(strict_types=1);

namespace Bill7189\TwigInitProfiler\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

#[AsCommand(
    name: 'twig-init-profiler:benchmark',
    description: 'Analyze Twig extensions for TwigRuntime migration potential',
)]
class BenchmarkTwigExtensionsCommand extends Command
{
    /**
     * @param list<string> $excludedClasses
     * @param list<string> $excludedPrefixes
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly array $excludedClasses = [],
        private readonly array $excludedPrefixes = [],
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Twig Extension Analysis');

        $extensions = $this->twig->getExtensions();
        $rows = [];
        $totalDeps = 0;
        $totalInline = 0;
        $totalRuntime = 0;

        foreach ($extensions as $extension) {
            $class = get_class($extension);

            if ($this->isExcluded($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            $paramCount = $constructor?->getNumberOfParameters() ?? 0;

            [$inline, $runtime] = $this->countMethods($extension);

            $status = ($paramCount > 0 && $inline > 0)
                ? '<fg=red>MIGRATE</>'
                : '<fg=green>OK</>';

            $rows[] = [
                $class,
                (string) $paramCount,
                (string) $inline,
                (string) $runtime,
                $status,
            ];

            $totalDeps += $paramCount;
            $totalInline += $inline;
            $totalRuntime += $runtime;
        }

        usort($rows, static fn(array $a, array $b): int => (int) $b[1] <=> (int) $a[1]);

        $io->table(
            ['Extension', 'Deps', 'Inline', 'Runtime', 'Status'],
            $rows,
        );

        $io->section('Summary');
        $io->listing([
            sprintf('Total constructor dependencies: <info>%d</info>', $totalDeps),
            sprintf('Inline methods: <info>%d</info>', $totalInline),
            sprintf('Runtime-delegated methods: <info>%d</info>', $totalRuntime),
            sprintf('Extensions needing migration: <info>%d</info>', count(array_filter($rows, static fn(array $r): bool => (int) $r[1] > 0))),
        ]);

        $io->note([
            'To measure per-extension instantiation time:',
            '1. Clear cache: bin/console cache:clear',
            '2. Load a page in the browser',
            '3. Open the Symfony profiler -> Twig Ext panel',
        ]);

        return Command::SUCCESS;
    }

    private function isExcluded(string $class): bool
    {
        if (in_array($class, $this->excludedClasses, true)) {
            return true;
        }

        foreach ($this->excludedPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($class, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{int, int} [inline count, runtime count]
     */
    private function countMethods(AbstractExtension $extension): array
    {
        $inline = 0;
        $runtime = 0;

        foreach (['getFunctions', 'getFilters', 'getTests'] as $registryMethod) {
            if (!method_exists($extension, $registryMethod)) {
                continue;
            }

            $items = $extension->$registryMethod();
            foreach ($items as $item) {
                $callable = $item->getCallable();
                if (is_array($callable) && is_object($callable[0]) && $callable[0] === $extension) {
                    $inline++;
                } elseif (is_array($callable) && is_string($callable[0]) && str_contains($callable[0], 'Runtime')) {
                    $runtime++;
                } else {
                    $inline++;
                }
            }
        }

        return [$inline, $runtime];
    }
}
