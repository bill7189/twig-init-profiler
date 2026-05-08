# Twig Init Profiler

A Symfony bundle that adds a **Twig Ext** panel to the Symfony Web Profiler showing how long each Twig extension takes to instantiate, plus a CLI command to spot extensions that are good candidates for migrating to `TwigRuntime`.

Heavy Twig extensions (lots of constructor dependencies) get instantiated on every request that touches Twig — even if their filters and functions are never called. This bundle measures that cost so you can act on it.

## Requirements

- PHP 8.1+
- Symfony 5.4+
- Twig 2.13+ or 3+

## Installation

```bash
composer require --dev bill7189/twig-init-profiler
```

Register the bundle for the dev environment in `config/bundles.php`:

```php
return [
    // ...
    Bill7189\TwigInitProfiler\Bill7189TwigInitProfilerBundle::class => ['dev' => true, 'test' => true],
];
```

That's it. The next request you make in the browser will show a **Twig Ext** entry in the Symfony toolbar.

## Configuration

By default the bundle profiles **every** non-abstract, non-synthetic service tagged `twig.extension`. To narrow it down, create `config/packages/dev/bill7189_twig_init_profiler.yaml`:

```yaml
bill7189_twig_init_profiler:
    excluded_classes:
        - Symfony\Bridge\Twig\Extension\WebLinkExtension
    excluded_prefixes:
        - 'Symfony\\'
        - 'Twig\\'
        - 'Sylius\\'
```

- `excluded_classes` — exact FQCNs to skip.
- `excluded_prefixes` — namespace prefixes to skip (matched with `str_starts_with`).

Both default to empty. Both filters apply to the profiler panel **and** the benchmark command.

## CLI Benchmark

The bundle also ships a console command that statically analyses each extension and reports on its constructor dependency count, plus how many filters/functions/tests are inline vs runtime-delegated:

```bash
bin/console twig-init-profiler:benchmark
```

Sample output:

```
 ----------------- ------ -------- --------- ----------
  Extension         Deps   Inline   Runtime   Status
 ----------------- ------ -------- --------- ----------
  ProductExtension     5        7         0   MIGRATE
  CatalogExtension     3        4         0   MIGRATE
  HelperExtension      0        2         0   OK
 ----------------- ------ -------- --------- ----------
```

`MIGRATE` flags extensions that have constructor dependencies but still register inline callables — those are the highest-impact candidates for `TwigRuntime`.

## How It Works

A compiler pass replaces each profiled extension's service definition with a factory that wraps the original instantiation in `hrtime()` measurements. Timing is captured at container resolution, then surfaced via a `DataCollector`. The pass is automatically a no-op when `debug.stopwatch` (i.e. dev/test mode) isn't present, so it's safe to register the bundle in any environment.

## License

MIT — see [LICENSE](LICENSE).
