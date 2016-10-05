<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class MakeAuthCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth {--views : Only scaffold the authentication views} {--lang=en : The language of the labels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'auth/register.stub' => 'auth/register.blade.php',
        'auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
        'auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        'layouts/app.stub' => 'layouts/app.blade.php',
        'home.stub' => 'home.blade.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $language = strtolower($this->option('lang'));

        $this->checkIfLanguageIsSupported($language);

        $this->createDirectories();

        $this->exportViews();

        $this->exportLanguageFile($language);

        if (! $this->option('views')) {
            file_put_contents(
                app_path('Http/Controllers/HomeController.php'),
                $this->compileControllerStub()
            );

            file_put_contents(
                base_path('routes/web.php'),
                file_get_contents(__DIR__.'/stubs/make/routes.stub'),
                FILE_APPEND
            );
        }

        $this->info('Authentication scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (! is_dir(base_path('resources/views/layouts'))) {
            mkdir(base_path('resources/views/layouts'), 0755, true);
        }

        if (! is_dir(base_path('resources/views/auth/passwords'))) {
            mkdir(base_path('resources/views/auth/passwords'), 0755, true);
        }

        if (! is_dir(base_path('resources/lang'))) {
            mkdir(base_path('resources/lang'), 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            copy(
                __DIR__.'/stubs/make/views/'.$key,
                base_path('resources/views/'.$value)
            );
        }
    }

    /**
     * Validate the language option.
     *
     * @param string $language
     */
    protected function checkIfLanguageIsSupported($language)
    {
        $supportedLanguages = $this->getSupportedLanguages();
        if (! in_array($language, $supportedLanguages)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Language %s is not supported. Supported languages: %s',
                    $language,
                    implode($supportedLanguages, ', ')
                )
            );
        }
    }

    /**
     * Get a list of languages found in resources/lang folder.
     *
     * @return array
     */
    protected function getSupportedLanguages()
    {
        $languageDirectory = __DIR__.'/stubs/make/resources/lang/';
        $supportedLanguageDirectoryPaths = glob($languageDirectory.'*', GLOB_ONLYDIR);

        return array_map(function ($path) use ($languageDirectory, $supportedLanguageDirectoryPaths){
            return str_replace($languageDirectory, '', $path);
        }, $supportedLanguageDirectoryPaths);
    }

    /**
     * Export the language translation file for the specified language.
     *
     * @param string $language
     */
    protected function exportLanguageFile($language)
    {
        if (! is_dir(base_path('resources/lang/'.$language))) {
            mkdir(base_path('resources/lang/'.$language), 0755, true);
        }

        if (! file_exists(base_path('resources/lang/'.$language.'/labels.php'))) {
            copy(
                __DIR__.'/stubs/make/resources/lang/'.$language.'/labels.php',
                base_path('resources/lang/'.$language.'/labels.php')
            );
        }
    }

    /**
     * Compiles the HomeController stub.
     *
     * @return string
     */
    protected function compileControllerStub()
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__.'/stubs/make/controllers/HomeController.stub')
        );
    }
}
