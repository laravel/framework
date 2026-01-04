# Laravel Request Boot Performance Notes

## Goals
- Catalog potential inefficiencies during request bootstrapping.
- Track evidence and measurements over time.

## Suspected Hot Spots (from code reading)

### Configuration loading (uncached)
- Scans config directory and merges all configuration files.
- Uses Finder and per-file array merges, including nested mergeable options.
- File IO and array work per request when config cache is disabled.

Files:
- src/Illuminate/Foundation/Bootstrap/LoadConfiguration.php

Notes:
- Finder walk in `getConfigurationFiles`.
- Full merge path in `loadConfigurationFiles`.

### Provider registration and manifest handling
- Provider list is built, manifest loaded/checked, and eager providers are registered.
- When config is not cached, additional provider merge includes `class_exists` checks.

Files:
- src/Illuminate/Foundation/Application.php
- src/Illuminate/Foundation/ProviderRepository.php
- src/Illuminate/Foundation/Bootstrap/RegisterProviders.php

Notes:
- `registerConfiguredProviders` + ProviderRepository `load`.
- `compileManifest` creates provider instances for each provider.

### Provider boot loop
- All registered providers are booted on each request in typical PHP-FPM setups.

Files:
- src/Illuminate/Foundation/Application.php

Notes:
- `boot()` walks all providers and invokes `boot` where present.

### Facade alias registration and package manifest
- Facade aliases merged with package manifest each request.
- Package manifest is read from disk (built if missing).

Files:
- src/Illuminate/Foundation/Bootstrap/RegisterFacades.php
- src/Illuminate/Foundation/PackageManifest.php

Notes:
- AliasLoader gets config aliases + manifest aliases every bootstrap.

### Middleware resolution and sorting
- Per-request resolution of route middleware (groups, aliases, excluded).
- Sorting includes class/interface/parent inspection and may recurse.

Files:
- src/Illuminate/Routing/Router.php
- src/Illuminate/Routing/SortedMiddleware.php

Notes:
- `resolveMiddleware` does group expansion and optional Reflection checks.
- `SortedMiddleware` uses `class_implements` and `class_parents`.

### Termination middleware pass
- Terminate path re-gathers route middleware + global middleware.
- Instantiates each middleware to check for `terminate`.

Files:
- src/Illuminate/Foundation/Http/Kernel.php

Notes:
- `terminateMiddleware` loops all middleware again.

## Measurements

### Methodology
- Enable timing via `LARAVEL_BOOTSTRAP_TIMING=1`.
- Use default log channel; timings log as `bootstrap.performance` with a `timings` array.
- Capture a few request types (e.g., homepage, API, 404) with/without cached config/routes.
- Repeat to smooth out variance (at least 5 requests per case).

Instrumentation locations:
- `bootstrapper.*` and `bootstrap.total` from `src/Illuminate/Foundation/Application.php`.
- `providers.register_configured` and `providers.boot` from `src/Illuminate/Foundation/Application.php`.
- `router.resolve_middleware` and `router.sort_middleware` from `src/Illuminate/Routing/Router.php`.

### Results
- Benchmark app: `../laravel-benchmarking-test-app`
- Config/routes cached via `php artisan optimize`
- Env: `LARAVEL_BOOTSTRAP_TIMING=1`, default log channel, 5 sequential GET `/` requests

Cold vs warm summary (ms):

| Metric | Cold (first request) | Warm avg (requests 2-5) |
| --- | --- | --- |
| bootstrap.total | 29.152 | 0.482 |
| providers.register_configured | 14.513 | 0.141 |
| providers.boot | 9.632 | 0.184 |
| bootstrapper.RegisterFacades | 3.705 | 0.032 |
| router.resolve_middleware | 0.524 | 0.021 |
| router.sort_middleware | 0.384 | 0.009 |

Notes:
- First request is markedly slower (cold start) even with caches, likely due to autoload/opcache and manifest reads.

Route variants (same server process, cached config/routes, 5 requests each):

| Route | Notes | bootstrap.total avg (ms) | providers.register_configured avg (ms) | providers.boot avg (ms) | router.resolve_middleware avg (ms) | router.sort_middleware avg (ms) |
| --- | --- | --- | --- | --- | --- | --- |
| `/` | Web route (cold + warm) | 6.151 (cold+warm avg) | 2.852 | 2.173 | 0.115 | 0.081 |
| `/` (warm only) | Requests 2-5 | 0.485 | 0.142 | 0.185 | 0.021 | 0.009 |
| `/api/ping` | `api` middleware group | 0.580 | 0.186 | 0.217 | 0.025 | 0.011 |
| `/missing` | 404 (no route middleware) | 0.610 | 0.206 | 0.222 | n/a | n/a |
| `/bench` | Custom `bench` group (5 no-ops + config-resolving middleware, warm-only) | 0.463 | 0.128 | 0.182 | 0.040 | 0.027 |

Notes:
- 404s skip route middleware resolution (no route match), so middleware timing entries are absent.

## Questions / Next Steps
- Confirm which paths run even with config/route cache enabled.
- Add lightweight timing hooks for bootstrapper steps, provider registration/boot, middleware resolution/sort.
- Capture baseline numbers under common configs (cached vs uncached).

## Synopsis
- Instrumentation shows cached config/routes still incur a large cold-start cost on the first request.
- Cold-start time (~30ms in this setup) concentrates in provider registration, provider boot, and facade alias registration.
- Warm requests are sub-millisecond for bootstrap with cached config/routes, even with a heavier middleware group.
- Middleware resolution and sorting are measurable but small relative to cold-start costs; 404s skip route middleware resolution.

## Optimization Order (Proposed)
1. Reduce cold-start overhead in provider registration/boot (eager provider count, deferred providers, manifest usage).
2. Reduce facade alias registration and package manifest read costs during cold start.
3. Audit provider list size and default provider set for the benchmarking app to quantify impact.
4. Only after cold-start improvements, pursue middleware resolution/sorting micro-optimizations.
5. Re-run cached and uncached benchmarks after each change to validate gains.

## Next Experiments (Concrete)
- Toggle provider count: remove nonessential providers in the benchmark app and re-measure cold/warm.
- Disable package discovery temporarily (empty `extra.laravel.dont-discover`) and compare cold start.
- Compare facade alias registration by trimming `app.aliases` in the benchmark app.
- Add a route with a large middleware group (10-20) and measure middleware resolution scaling.
- Uncached baseline: `config:clear` + `route:clear`, re-run the same routes.

## Measurement Checklist
- Set `LARAVEL_BOOTSTRAP_TIMING=1` in `.env`.
- Ensure known state: run `php artisan optimize` for cached runs or `config:clear`/`route:clear` for uncached runs.
- Clear logs: truncate `storage/logs/laravel.log`.
- Start server (`php artisan serve`) and hit each route 5 times.
- Record cold (first request) and warm (requests 2-5) metrics.
- Append results to the table with route, middleware group, and cache state.
