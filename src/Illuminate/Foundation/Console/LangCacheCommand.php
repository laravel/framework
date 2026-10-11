<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'lang:cache')]
class LangCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster translation loading';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new lang cache command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function handle()
    {
        $this->callSilent('lang:clear');

        $translations = $this->getTranslations($this->getFreshLoader());

        $path = $this->laravel->getCachedTranslationsPath();

        $this->files->put(
            $path, '<?php return '.var_export($translations, true).';'.PHP_EOL
        );

        try {
            require $path;
        } catch (Throwable $e) {
            $this->files->delete($path);

            throw new LogicException('Your translation files are not serializable.', 0, $e);
        }

        $this->components->info('Translations cached successfully.');
    }

    /**
     * Get a file loader that reads the translation files directly.
     *
     * @return \Illuminate\Translation\FileLoader
     *
     * @throws \LogicException
     */
    protected function getFreshLoader()
    {
        // Resolving the translator first lets packages register their translation paths and
        // namespaces. We then copy them into a new loader so the translations are always
        // read from the files, even when the current loader is reading from the cache.
        $this->laravel->make('translator');

        $loader = $this->laravel->make('translation.loader');

        if (! $loader instanceof FileLoader) {
            throw new LogicException('Translations can only be cached when using the file loader.');
        }

        $fresh = new FileLoader($this->files, $loader->paths());

        foreach ($loader->jsonPaths() as $path) {
            $fresh->addJsonPath($path);
        }

        foreach ($loader->namespaces() as $namespace => $hint) {
            $fresh->addNamespace($namespace, $hint);
        }

        return $fresh;
    }

    /**
     * Get all of the translations, keyed by locale, namespace, and group.
     *
     * @param  \Illuminate\Translation\FileLoader  $loader
     * @return array
     */
    protected function getTranslations(FileLoader $loader)
    {
        $translations = [];

        foreach ($loader->paths() as $path) {
            foreach ($this->getGroups($path) as [$locale, $group]) {
                $translations[$locale]['*'][$group] = $loader->load($locale, $group, '*');
            }
        }

        foreach ($loader->namespaces() as $namespace => $hint) {
            $directories = [$hint];

            foreach ($loader->paths() as $path) {
                $directories[] = "{$path}/vendor/{$namespace}";
            }

            foreach ($directories as $directory) {
                foreach ($this->getGroups($directory) as [$locale, $group]) {
                    $translations[$locale][$namespace][$group] = $loader->load($locale, $group, $namespace);
                }
            }
        }

        foreach (array_merge($loader->jsonPaths(), $loader->paths()) as $path) {
            foreach ($this->files->glob("{$path}/*.json") as $file) {
                $locale = basename($file, '.json');

                $translations[$locale]['*']['*'] = $loader->load($locale, '*', '*');
            }
        }

        return $translations;
    }

    /**
     * Get the locale and group of every PHP translation file in the given directory.
     *
     * @param  string  $directory
     * @return array<int, array{string, string}>
     */
    protected function getGroups($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            return [];
        }

        $groups = [];

        foreach ($this->files->directories($directory) as $localeDirectory) {
            $locale = basename($localeDirectory);

            if ($locale === 'vendor') {
                continue;
            }

            foreach ($this->files->allFiles($localeDirectory) as $file) {
                if ($file->getExtension() === 'php') {
                    $groups[] = [$locale, str_replace('\\', '/', substr($file->getRelativePathname(), 0, -4))];
                }
            }
        }

        return $groups;
    }
}
