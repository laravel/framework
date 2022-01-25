<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Events\VendorTagPublished;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\MountManager;
use ReflectionClass;

class VendorPublishCommand extends Command
{
    /**
     * The directory descriptions.
     *
     * @var array
     */
    protected $directoryDescriptions = [
        'app/Providers' => 'providers',
        'config' => 'config',
        'database/migrations' => 'migrations',
        'database' => 'database files',
        'docker' => 'docker files',
        'public' => 'assets',
        'resources/views' => 'views',
        'resources/lang' => 'translations',
        'sail' => 'binary',
    ];

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The provider to publish.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * The tags to publish.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'vendor:publish {package? : The package that has assets you want to publish}
                    {--force : Overwrite any existing files}
                    {--all : Publish assets for all service providers without prompt}
                    {--provider= : The service provider that has assets you want to publish}
                    {--tag=* : One or many tags that have assets you want to publish}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'vendor:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish any publishable assets from vendor packages';

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
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
     */
    public function handle()
    {
        $this->determineWhatShouldBePublished();

        if ($this->argument('package') && ! $this->providers) {
            return 0;
        }

        foreach ($this->tags ?: [null] as $tag) {
            $this->publishTag($tag);
        }

        $this->info('Publishing complete.');
    }

    /**
     * Determine the provider or tag(s) to publish.
     *
     * @return void
     */
    protected function determineWhatShouldBePublished()
    {
        if (! $this->argument('package') && $this->option('all')) {
            return;
        }

        [$this->providers, $this->tags] = [
            (array) $this->option('provider'), (array) $this->option('tag'),
        ];

        if ($package = $this->argument('package')) {
            $this->discoverFromPackage($package);

            if ($this->providers) {
                $this->promptForPackageAndTag();
            }
        } elseif (! $this->providers && ! $this->tags) {
            $this->promptForPackageAndTag();
        }
    }

    /**
     * Determine the provider to publish from the given package name.
     *
     * @param  string  $package
     * @return void
     */
    protected function discoverFromPackage($package)
    {
        $this->providers = collect(ServiceProvider::publishableProviders())->map(function ($provider) use ($package) {
            $providerPackage = $this->packageFromProvider($provider);

            return $providerPackage == $package ? $provider : null;
        })->filter()->values()->whenEmpty(function () use ($package) {
            $this->comment('No publishable resources for package ['.$package.'].');
        })->toArray();
    }

    /**
     * Get the package name from the provider class.
     *
     * @return string
     */
    protected function packageFromProvider($provider)
    {
        $currentPackageFolder = dirname((new ReflectionClass($provider))->getFileName());

        $providerPackage = null;

        while (true) {
            if ($currentPackageFolder == dirname(base_path())) {
                return;
            }

            $composerFileName = "$currentPackageFolder/composer.json";

            if (File::exists($composerFileName)) {
                return json_decode(File::get($composerFileName), true)['name'];
            }

            $currentPackageFolder = dirname($currentPackageFolder);
        }
    }

    /**
     * Prompt for which package or tag to publish.
     *
     * @return void
     */
    protected function promptForPackageAndTag()
    {
        if (! $this->providers) {
            $packageChoice = $this->choice(
                'Which packages would you like to publish?',
                $this->packageChoices()
            );

            $this->parsePackageChoice($packageChoice);
        }

        if (! $this->tags) {
            $tagChoice = 'all';

            if (! $this->option('all') && count($tagChoices = $this->tagChoices()) > 1) {
                $tagChoice = $this->choice(
                    'Would you like to publish a specific tag?',
                    collect(['all' => 'Publish all files'] + $tagChoices)->sortKeys()->toArray(),
                    'all',
                );
            }

            $this->parseTagChoice($tagChoice);
        }
    }

    /**
     * Finds a description from the given provider.
     *
     * @return array
     */
    protected function providerDescription($provider)
    {
        return collect(ServiceProvider::pathsToPublish($provider, null))
            ->map(fn ($to) => $this->directoryDescription($to))
            ->toArray();
    }

    /**
     * Finds a description from the given directory.
     *
     * @return string
     */
    protected function directoryDescription($directory)
    {
        return collect($this->directoryDescriptions)->first(
            fn ($description, $directoryWithDescription) => str($directory)->startsWith(base_path($directoryWithDescription)),
            'miscellaneous files',
        );
    }

    /**
     * The package choices available via the prompt.
     *
     * @return array
     */
    protected function packageChoices()
    {
        return collect($this->publishableProviders())->map(function ($providers) {
            return str(collect($providers)
                ->map(fn ($provider) => $this->providerDescription($provider))
                ->flatten()
                ->unique()
                ->sort()
                ->implode(', '))->ucfirst();
        })->sortKeys()->toArray();
    }

    /**
     * The tag choices available via the prompt.
     *
     * @return array
     */
    protected function tagChoices()
    {
        $tags = collect();

        foreach ($this->providers as $provider) {
            $pathsToPublish = $provider::$publishes[$provider] ?? [];

            $tags = $tags->merge(collect(ServiceProvider::$publishGroups)->filter(
                fn ($paths, $tag) => collect($paths)->filter(
                    fn ($to, $from) => isset($pathsToPublish[$from]),
                )->isNotEmpty()
            )->map(
                fn ($paths) => collect($paths)->map(
                    fn ($to) => str($to)->replace(base_path().'/', '')
                )->filter()->unique()->implode(', ')
            )->toArray());
        }

        return $tags->unique()->toArray();
    }

    /**
     * The choices available via the prompt.
     *
     * @return array
     */
    protected function publishableProviders()
    {
        return collect(ServiceProvider::publishableProviders())->groupBy(function ($provider) {
            return $this->packageFromProvider($provider);
        })->map->toArray()->toArray();
    }

    /**
     * Parse the package answer that was given via the prompt.
     *
     * @param  string  $choice
     * @return void
     */
    protected function parsePackageChoice($choice)
    {
        $this->providers = $this->publishableProviders()[$choice];
    }

    /**
     * Parse the tag answer that was given via the prompt.
     *
     * @param  string  $choice
     * @return void
     */
    protected function parseTagChoice($choice)
    {
        if ($choice != 'all') {
            $this->tags = [$choice];
        }
    }

    /**
     * Publishes the assets for a tag.
     *
     * @param  string|null  $tag
     * @return mixed
     */
    protected function publishTag($tag)
    {
        $published = false;

        $pathsToPublish = $this->pathsToPublish($tag);

        foreach ($pathsToPublish as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published === false) {
            $this->comment('No publishable resources for tag ['.$tag.'].');
        } else {
            $this->laravel['events']->dispatch(new VendorTagPublished($tag, $pathsToPublish));
        }
    }

    /**
     * Get all of the paths to publish.
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        if (! $this->providers) {
            return ServiceProvider::pathsToPublish(null, $tag);
        }

        $pathsToPublish = collect();

        foreach ($this->providers as $provider) {
            $pathsToPublish = $pathsToPublish->merge(
                ServiceProvider::pathsToPublish($provider, $tag)
            );
        }

        return $pathsToPublish->toArray();
    }

    /**
     * Publish the given item from and to the given location.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->error("Can't locate path: <{$from}>");
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if (! $this->files->exists($to) || $this->option('force')) {
            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    /**
     * Move all the files in the given MountManager.
     *
     * @param  \League\Flysystem\MountManager  $manager
     * @return void
     */
    protected function moveManagedFiles($manager)
    {
        foreach ($manager->listContents('from://', true) as $file) {
            $path = Str::after($file['path'], 'from://');

            if ($file['type'] === 'file' && (! $manager->fileExists('to://'.$path) || $this->option('force'))) {
                $manager->write('to://'.$path, $manager->read($file['path']));
            }
        }
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}
