<?php

namespace Illuminate\Foundation\Console;

use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

class DocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs {page? : The documentation page to open} {section? : The section of the page to open}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Access the Laravel documentation with ease.';

    /**
     * The console command help text.
     *
     * @var string
     */
    protected $help = 'If you would like to perform a content search against the documention, you may call: <fg=green>php artisan docs -- </><fg=green;options=bold;>search query here</>';

    /**
     * The HTTP client.
     *
     * @var \Illuminate\Http\Client\Factory
     */
    protected $http;

    /**
     * The cache.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The custom url opener.
     *
     * @var callable|null
     */
    protected $urlOpener;

    /**
     * The custom documentation version to open.
     *
     * @var string|null
     */
    protected $version;

    /**
     * The operating system family.
     *
     * @var string
     */
    protected $systemOsFamily = PHP_OS_FAMILY;

    public function __construct(Http $http, Cache $cache)
    {
        parent::__construct();

        $this->http = $http;

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->openUrl();
        } catch (ProcessFailedException $e) {
            if ($e->getProcess()->getExitCodeText() === 'Interrupt') {
                return $e->getProcess()->getExitCode();
            }

            throw $e;
        }

        $this->refreshDocs();

        return Command::SUCCESS;
    }

    /**
     * Open the documentation URL.
     *
     * @return void
     */
    protected function openUrl()
    {
        with($this->url(), function ($url) {
            $this->components->info("Opening the docs to: <fg=yellow>{$url}</>");

            $this->open($url);
        });
    }

    /**
     * The URL to the documentation page.
     *
     * @return string
     */
    protected function url()
    {
        if ($this->isSearching()) {
            return "https://laravel.com/docs/{$this->version()}?".Arr::query([
                'q' => $this->searchQuery(),
            ]);
        }

        return with($this->page(), function ($page) {
            return trim("https://laravel.com/docs/{$this->version()}/{$page}#{$this->section($page)}", '#/');
        });
    }

    /**
     * The page the user is opening.
     *
     * @return string
     */
    protected function page()
    {
        return with($this->resolvePage(), function ($page) {
            if ($page === null) {
                $this->components->warn('Unable to determine the page you are trying to visit.');

                return '/';
            }

            return $page;
        });
    }

    /**
     * Determine the page to open.
     *
     * @return ?string
     */
    protected function resolvePage()
    {
        if ($this->option('no-interaction') && $this->didNotRequestPage()) {
            return '/';
        }

        return $this->didNotRequestPage()
            ? $this->askForPage()
            : $this->guessPage();
    }

    /**
     * Determine if the user requested a specific page when calling the command.
     *
     * @return bool
     */
    protected function didNotRequestPage()
    {
        return $this->argument('page') === null;
    }

    /**
     * Ask the user which page they would like to open.
     *
     * @return ?string
     */
    protected function askForPage()
    {
        return $this->askForPageViaCustomStrategy() ?? $this->askForPageViaAutocomplete();
    }

    /**
     * Ask the user which page they would like to open via a custom strategy.
     *
     * @return ?string
     */
    protected function askForPageViaCustomStrategy()
    {
        try {
            $strategy = require Env::get('ARTISAN_DOCS_ASK_STRATEGY');
        } catch (Throwable $e) {
            return null;
        }

        if (! is_callable($strategy)) {
            return null;
        }

        return $strategy($this) ?? '/';
    }

    /**
     * Ask the user which page they would like to open using autocomplete.
     *
     * @return ?string
     */
    protected function askForPageViaAutocomplete()
    {
        $choice = $this->components->choice(
            'Which page would you like to open?',
            $this->pages()->mapWithKeys(fn ($option) => [
                Str::lower($option['title']) => $option['title'],
            ])->all(),
            'installation',
            3
        );

        return $this->pages()->filter(
            fn ($page) => $page['title'] === $choice || Str::lower($page['title']) === $choice
        )->keys()->first() ?: null;
    }

    /**
     * Guess the page the user is requesting to open.
     *
     * @return ?string
     */
    protected function guessPage()
    {
        return $this->pages()
            ->filter(fn ($page) => str_starts_with(
                Str::slug($page['title'], ' '),
                Str::slug($this->argument('page'), ' ')
            ))->keys()->first() ?? $this->pages()->map(fn ($page) => similar_text(
                Str::slug($page['title'], ' '),
                Str::slug($this->argument('page'), ' '),
            ))
            ->filter(fn ($score) => $score >= min(3, Str::length($this->argument('page'))))
            ->sortDesc()
            ->keys()
            ->sortByDesc(fn ($slug) => Str::contains(
                Str::slug($this->pages()[$slug]['title'], ' '),
                Str::slug($this->argument('page'), ' ')
            ) ? 1 : 0)
            ->first();
    }

    /**
     * The section the user specifically asked to open.
     *
     * @param  string  $page
     * @return ?string
     */
    protected function section($page)
    {
        return $this->didNotRequestSection()
            ? null
            : $this->guessSection($page);
    }

    /**
     * Determine if the user requested a specific section when calling the command.
     *
     * @return bool
     */
    protected function didNotRequestSection()
    {
        return $this->argument('section') === null;
    }

    /**
     * Guess the section the user is requesting to open.
     *
     * @param  string  $page
     * @return ?string
     */
    protected function guessSection($page)
    {
        return $this->sectionsFor($page)
            ->filter(fn ($section) => str_starts_with(
                Str::slug($section['title'], ' '),
                Str::slug($this->argument('section'), ' ')
            ))->keys()->first() ?? $this->sectionsFor($page)->map(fn ($section) => similar_text(
                Str::slug($section['title'], ' '),
                Str::slug($this->argument('section'), ' '),
            ))
            ->filter(fn ($score) => $score >= min(3, Str::length($this->argument('section'))))
            ->sortDesc()
            ->keys()
            ->sortByDesc(fn ($slug) => Str::contains(
                Str::slug($this->sectionsFor($page)[$slug]['title'], ' '),
                Str::slug($this->argument('section'), ' ')
            ) ? 1 : 0)
            ->first();
    }

    /**
     * Open the URL.
     *
     * @param  string  $url
     * @return void
     */
    protected function open($url)
    {
        ($this->urlOpener ?? function ($url) {
            if (Env::get('ARTISAN_DOCS_OPEN_STRATEGY')) {
                $this->openViaCustomStrategy($url);
            } elseif (in_array($this->systemOsFamily, ['Darwin', 'Windows', 'Linux'])) {
                $this->openViaBuiltInStrategy($url);
            } else {
                $this->components->warn('Unable to open the URL on your system. You will need to open it yourself.');
            }
        })($url);
    }

    /**
     * Open the URL via a custom strategy.
     *
     * @param  string  $url
     * @return void
     */
    protected function openViaCustomStrategy($url)
    {
        try {
            $command = require Env::get('ARTISAN_DOCS_OPEN_STRATEGY');
        } catch (Throwable $e) {
            $command = null;
        }

        if (! is_callable($command)) {
            $this->components->warn('Unable to open the URL with your custom strategy. You will need to open it yourself.');

            return;
        }

        $command($url);
    }

    /**
     * Open the URL via the built in strategy.
     *
     * @param  string  $url
     * @return void
     */
    protected function openViaBuiltInStrategy($url)
    {
        $process = tap(Process::fromShellCommandline(match ($this->systemOsFamily) {
            'Darwin' => 'open',
            'Windows' => 'start',
            'Linux' => 'xdg-open',
        }.' '.escapeshellarg($url)))->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * The available sections for the page.
     *
     * @param  string  $page
     * @return \Illuminate\Support\Collection
     */
    public function sectionsFor($page)
    {
        return new Collection($this->pages()[$page]['sections']);
    }

    /**
     * The pages available to open.
     *
     * @return \Illuminate\Support\Collection
     */
    public function pages()
    {
        return new Collection($this->docs()['pages']);
    }

    /**
     * The documentation.
     *
     * @return \Illuminate\Support\Collection
     */
    public function docs()
    {
        return $this->cache->remember(
            "artisan.docs.{{$this->version()}}.index",
            CarbonInterval::months(2),
            fn () => $this->fetchDocs()->throw()->collect()
        );
    }

    /**
     * Refresh the documentation.
     *
     * @return void
     */
    protected function refreshDocs()
    {
        with($this->fetchDocs(), function ($response) {
            if ($response->successful()) {
                $this->cache->put("artisan.docs.{{$this->version()}}.index", $response->collect(), CarbonInterval::months(2));
            }
        });
    }

    /**
     * Fetch the documentation.
     *
     * @return \Illuminate\Http\Client\Response
     */
    protected function fetchDocs()
    {
        return $this->http->get("https://laravel.com/docs/{$this->version()}/index.json");
    }

    /**
     * The version of the docs to open.
     *
     * @return string
     */
    protected function version()
    {
        return Str::before(($this->version ?? $this->laravel->version()), '.').'.x';
    }

    /**
     * The search to perform.
     *
     * @return string
     */
    protected function searchQuery()
    {
        return Collection::make($_SERVER['argv'])->skip(3)->implode(' ');
    }

    /**
     * Determine if the command is intended to perform a search.
     *
     * @return bool
     */
    protected function isSearching()
    {
        return ($_SERVER['argv'][2] ?? null) === '--';
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        if ($this->isSearching()) {
            $this->ignoreValidationErrors();
        }
    }

    /**
     * Set the documentation version.
     *
     * @param  string  $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set a custom URL opener.
     *
     * @param  callable|null  $opener
     * @return $this
     */
    public function setUrlOpener($opener)
    {
        $this->urlOpener = $opener;

        return $this;
    }

    /**
     * Set the system operating system family.
     *
     * @param  string  $family
     * @return $this
     */
    public function setSystemOsFamily($family)
    {
        $this->systemOsFamily = $family;

        return $this;
    }
}
