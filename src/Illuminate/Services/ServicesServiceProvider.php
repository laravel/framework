<?php

namespace Illuminate\Services;

use Illuminate\Routing\Router;
use Illuminate\Services\Attributes\RemoteName;
use Illuminate\Services\Http\AuthenticateService;
use Illuminate\Services\Http\HandleServiceRequest;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

class ServicesServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ServiceManager::class, fn ($app) => new ServiceManager($app));
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if (! class_exists(Router::class)) {
            return;
        }

        /**
         * Register a POST route for each job that other services may call.
         *
         * @param  array<int, class-string>  $jobs
         * @param  string  $prefix
         * @return void
         */
        Router::macro('services', function (array $jobs, string $prefix = '_services') {
            foreach ($jobs as $job) {
                $name = ((new ReflectionClass($job))->getAttributes(RemoteName::class)[0] ?? null)?->newInstance()->name
                    ?? Str::snake(class_basename($job));

                /** @var \Illuminate\Routing\Router $this */
                $this->post(trim($prefix, '/').'/'.$name, HandleServiceRequest::class)
                    ->middleware(AuthenticateService::class)
                    ->defaults('_service_job', $job);
            }
        });
    }
}
