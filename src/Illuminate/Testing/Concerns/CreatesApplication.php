<?php

namespace Illuminate\Testing\Concerns;

use RuntimeException;

trait CreatesApplication
{
    /**
     * The application resolver callback.
     *
     * @var \Closure|null
     */
    protected static $applicationResolver;

    /**
     * Set the application resolver callback.
     *
     * @param  \Closure|null  $resolver
     * @return void
     */
    public static function resolveApplicationUsing($resolver)
    {
        static::$applicationResolver = $resolver;
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     *
     * @throws \RuntimeException
     */
    protected function createApplication()
    {
        $applicationResolver = static::$applicationResolver ?: function () {
            if (trait_exists(\Tests\CreatesApplication::class)) {
                $applicationCreator = new class
                {
                    use \Tests\CreatesApplication;
                };

                return $applicationCreator->createApplication();
            } elseif (file_exists(getcwd().'/bootstrap/app.php')) {
                $app = require getcwd().'/bootstrap/app.php';

                $app->make(Kernel::class)->bootstrap();

                return $app;
            }

            throw new RuntimeException('Unable to resolve application.');
        };

        return $applicationResolver();
    }
}
