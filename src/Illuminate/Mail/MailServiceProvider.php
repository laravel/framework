<?php

namespace Illuminate\Mail;

use Illuminate\Support\ServiceProvider;
use Swift_DependencyContainer;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerIlluminateMailer();
        $this->registerMarkdownRenderer();
    }

    /**
     * Register the Illuminate mailer instance.
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mailer', function () {
            if ($domain = $this->app->make('config')->get('mail.domain')) {
                Swift_DependencyContainer::getInstance()
                    ->register('mime.idgenerator.idright')
                    ->asValue($domain);
            }
            $manager =  new MailerManager(
                $this->app,
                $this->app['view'],
                $this->app['events']
            );

            if ($this->app->bound('queue')) {
                $manager->setQueue($this->app['queue']);
            }

            return $manager;
        });

        $this->app->alias('mailer',MailerManager::class);
    }

    /**
     * Register the Markdown renderer instance.
     *
     * @return void
     */
    protected function registerMarkdownRenderer()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => $this->app->resourcePath('views/vendor/mail'),
            ], 'laravel-mail');
        }

        $this->app->singleton(Markdown::class, function () {
            $config = $this->app->make('config');

            return new Markdown($this->app->make('view'), [
                'theme' => $config->get('mail.markdown.theme', 'default'),
                'paths' => $config->get('mail.markdown.paths', []),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mailer', Markdown::class, MailerManager::class
        ];
    }
}
