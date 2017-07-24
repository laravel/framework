<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Routing\Redirector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;

class FormRequestServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->afterResolving(ValidatesWhenResolved::class, function ($resolved) {
            $resolved->validate();
        });

        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $this->initializeRequest($request, $app['request']);

            $request->setContainer($app)->setRedirector($app->make(Redirector::class));
        });
    }

    /**
     * Initialize the form request with data from the given request.
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $form
     * @param  \Symfony\Component\HttpFoundation\Request  $current
     * @return void
     */
    protected function initializeRequest(FormRequest $form, Request $current)
    {
        $files = $current->files->all();

        $files = is_array($files) ? array_filter($files) : $files;

        $form->initialize(
            $current->query->all(), $current->request->all(), $current->attributes->all(),
            $current->cookies->all(), $files, $current->server->all(), $current->getContent()
        );

        $form->setJson($current->json());

        if ($session = $current->getSession()) {
            $form->setLaravelSession($session);
        }

        $form->setUserResolver($current->getUserResolver());

        $form->setRouteResolver($current->getRouteResolver());
    }
}
