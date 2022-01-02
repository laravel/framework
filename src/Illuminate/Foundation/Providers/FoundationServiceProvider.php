<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;
use Illuminate\Foundation\MaintenanceModeManager;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\LoggedExceptionCollection;
use Illuminate\Testing\ParallelTestingServiceProvider;
use Illuminate\Validation\ValidationException;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        FormRequestServiceProvider::class,
        ParallelTestingServiceProvider::class,
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Exceptions/views' => $this->app->resourcePath('views/errors/'),
            ], 'laravel-errors');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes(
                $this->stubsToPublish(),
                'laravel-stubs'
            );
        }

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRequestValidation();
        $this->registerRequestSignatureValidation();
        $this->registerExceptionTracking();
        $this->registerMaintenanceModeManager();
    }

    /**
     * Register the "validate" macro on the request.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function registerRequestValidation()
    {
        Request::macro('validate', function (array $rules, ...$params) {
            return validator()->validate($this->all(), $rules, ...$params);
        });

        Request::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
            try {
                return $this->validate($rules, ...$params);
            } catch (ValidationException $e) {
                $e->errorBag = $errorBag;

                throw $e;
            }
        });
    }

    /**
     * Register the "hasValidSignature" macro on the request.
     *
     * @return void
     */
    public function registerRequestSignatureValidation()
    {
        Request::macro('hasValidSignature', function ($absolute = true) {
            return URL::hasValidSignature($this, $absolute);
        });

        Request::macro('hasValidRelativeSignature', function () {
            return URL::hasValidSignature($this, $absolute = false);
        });

        Request::macro('hasValidSignatureWhileIgnoring', function ($ignoreQuery = [], $absolute = true) {
            return URL::hasValidSignature($this, $absolute, $ignoreQuery);
        });
    }

    /**
     * Register an event listener to track logged exceptions.
     *
     * @return void
     */
    protected function registerExceptionTracking()
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        $this->app->instance(
            LoggedExceptionCollection::class,
            new LoggedExceptionCollection
        );

        $this->app->make('events')->listen(MessageLogged::class, function ($event) {
            if (isset($event->context['exception'])) {
                $this->app->make(LoggedExceptionCollection::class)
                        ->push($event->context['exception']);
            }
        });
    }

    /**
     * Register the maintenance mode manager service.
     *
     * @return void
     */
    public function registerMaintenanceModeManager()
    {
        $this->app->singleton(MaintenanceModeManager::class);

        $this->app->bind(
            MaintenanceModeContract::class,
            fn () => $this->app->make(MaintenanceModeManager::class)->driver()
        );
    }

    /**
     * Return array of publishable stubs
     *
     * @return array
     */
    private function stubsToPublish()
    {
        return [
            realpath(__DIR__.'/../Console/stubs/cast.stub') => $this->app->basePath('stubs/cast.stub'),
            realpath(__DIR__.'/../Console/stubs/console.stub') => $this->app->basePath('stubs/console.stub'),
            realpath(__DIR__.'/../Console/stubs/event.stub') => $this->app->basePath('stubs/event.stub'),
            realpath(__DIR__.'/../Console/stubs/job.queued.stub') => $this->app->basePath('stubs/job.queued.stub'),
            realpath(__DIR__.'/../Console/stubs/job.stub') => $this->app->basePath('stubs/job.stub'),
            realpath(__DIR__.'/../Console/stubs/mail.stub') => $this->app->basePath('stubs/mail.stub'),
            realpath(__DIR__.'/../Console/stubs/markdown-mail.stub') => $this->app->basePath('stubs/markdown-mail.stub'),
            realpath(__DIR__.'/../Console/stubs/markdown-notification.stub') => $this->app->basePath('stubs/markdown-notification.stub'),
            realpath(__DIR__.'/../Console/stubs/model.pivot.stub') => $this->app->basePath('stubs/model.pivot.stub'),
            realpath(__DIR__.'/../Console/stubs/model.stub') => $this->app->basePath('stubs/model.stub'),
            realpath(__DIR__.'/../Console/stubs/notification.stub') => $this->app->basePath('stubs/notification.stub'),
            realpath(__DIR__.'/../Console/stubs/observer.plain.stub') => $this->app->basePath('stubs/observer.plain.stub'),
            realpath(__DIR__.'/../Console/stubs/observer.stub') => $this->app->basePath('stubs/observer.stub'),
            realpath(__DIR__.'/../Console/stubs/policy.plain.stub') => $this->app->basePath('stubs/policy.plain.stub'),
            realpath(__DIR__.'/../Console/stubs/policy.stub') => $this->app->basePath('stubs/policy.stub'),
            realpath(__DIR__.'/../Console/stubs/provider.stub') => $this->app->basePath('stubs/provider.stub'),
            realpath(__DIR__.'/../Console/stubs/request.stub') => $this->app->basePath('stubs/request.stub'),
            realpath(__DIR__.'/../Console/stubs/resource-collection.stub') => $this->app->basePath('stubs/resource-collection.stub'),
            realpath(__DIR__.'/../Console/stubs/resource.stub') => $this->app->basePath('stubs/resource.stub'),
            realpath(__DIR__.'/../Console/stubs/rule.stub') => $this->app->basePath('stubs/rule.stub'),
            realpath(__DIR__.'/../Console/stubs/scope.stub') => $this->app->basePath('stubs/scope.stub'),
            realpath(__DIR__.'/../Console/stubs/test.stub') => $this->app->basePath('stubs/test.stub'),
            realpath(__DIR__.'/../Console/stubs/test.unit.stub') => $this->app->basePath('stubs/test.unit.stub'),
            realpath(__DIR__.'/../Console/stubs/view-component.stub') => $this->app->basePath('stubs/view-component.stub'),
            realpath(__DIR__.'/../../Database/Console/Factories/stubs/factory.stub') => $this->app->basePath('stubs/factory.stub'),
            realpath(__DIR__.'/../../Database/Console/Seeds/stubs/seeder.stub') => $this->app->basePath('stubs/seeder.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.create.stub') => $this->app->basePath('stubs/migration.create.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.stub') => $this->app->basePath('stubs/migration.stub'),
            realpath(__DIR__.'/../../Database/Migrations/stubs/migration.update.stub') => $this->app->basePath('stubs/migration.update.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.api.stub') => $this->app->basePath('stubs/controller.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.invokable.stub') => $this->app->basePath('stubs/controller.invokable.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.api.stub') => $this->app->basePath('stubs/controller.model.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.model.stub') => $this->app->basePath('stubs/controller.model.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.api.stub') => $this->app->basePath('stubs/controller.nested.api.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.nested.stub') => $this->app->basePath('stubs/controller.nested.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.plain.stub') => $this->app->basePath('stubs/controller.plain.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/controller.stub') => $this->app->basePath('stubs/controller.stub'),
            realpath(__DIR__.'/../../Routing/Console/stubs/middleware.stub') => $this->app->basePath('stubs/middleware.stub'),
        ];
    }
}
