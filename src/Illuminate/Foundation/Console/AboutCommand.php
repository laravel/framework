<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'about')]
class AboutCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'about {--only= : The section to display}
                {--json : Output the information as JSON}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'about';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display basic information about your application';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * The data to display.
     *
     * @var array
     */
    protected static $data = [];

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->gatherApplicationInformation();

        collect(static::$data)->sortBy(function ($data, $key) {
            $index = array_search($key, ['Environment', 'Cache', 'Drivers']);

            if ($index === false) {
                return 99;
            }

            return $index;
        })
            ->filter(function ($data, $key) {
                return $this->option('only') ? in_array(Str::of($key)->lower()->snake(), $this->sections()) : true;
            })
            ->pipe(fn ($data) => $this->display($data));

        $this->newLine();

        return 0;
    }

    /**
     * Display the application information.
     *
     * @param  \Illuminate\Support\Collection  $data
     * @return void
     */
    protected function display($data)
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
    }

    /**
     * Display the application information as a detail view.
     *
     * @param  \Illuminate\Support\Collection  $data
     * @return void
     */
    protected function displayDetail($data)
    {
        $data->each(function ($data, $section) {
            $this->newLine();

            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$section.'</>');

            sort($data);

            foreach ($data as $detail) {
                [$label, $value] = $detail;

                $this->components->twoColumnDetail($label, value($value));
            }
        });
    }

    /**
     * Display the application information as JSON.
     *
     * @param  \Illuminate\Support\Collection  $data
     * @return void
     */
    protected function displayJson($data)
    {
        $output = $data->flatMap(function ($data, $section) {
            return [(string) Str::of($section)->snake() => collect($data)->mapWithKeys(fn ($item, $key) => [(string) Str::of($item[0])->lower()->snake() => value($item[1])])];
        });

        $this->output->writeln(strip_tags(json_encode($output)));
    }

    /**
     * Gather information about the application.
     *
     * @return array
     */
    protected function gatherApplicationInformation()
    {
        static::add('Environment', [
            'Laravel Version' => $this->laravel->version(),
            'PHP Version' => phpversion(),
            'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
            'Environment' => $this->laravel->environment(),
            'Debug Mode' => config('app.debug') ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF',
            'Application Name' => config('app.name'),
            'URL' => Str::of(config('app.url'))->replace(['http://', 'https://'], ''),
            'Maintenance Mode' => $this->laravel->isDownForMaintenance() ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF',
        ]);

        static::add('Cache', [
            'Config' => file_exists($this->laravel->bootstrapPath('cache/config.php')) ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Routes' => file_exists($this->laravel->bootstrapPath('cache/routes-v7.php')) ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Events' => file_exists($this->laravel->bootstrapPath('cache/events.php')) ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Views' => $this->hasPhpFiles($this->laravel->storagePath('framework/views')) ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
        ]);

        static::add('Drivers', array_filter([
            'Broadcasting' => config('broadcasting.default'),
            'Cache' => config('cache.default'),
            'Database' => config('database.default'),
            'Mail' => config('mail.default'),
            'Octane' => config('octane.server'),
            'Queue' => config('queue.default'),
            'Scout' => config('scout.driver'),
            'Session' => config('session.driver'),
        ]));
    }

    /**
     * Determine whether the given directory has PHP files.
     *
     * @param  string  $path
     * @return bool
     */
    protected function hasPhpFiles(string $path): bool
    {
        return count(glob($path.'/*.php')) > 0;
    }

    /**
     * Get the sections provided to the command.
     *
     * @return array
     */
    protected function sections()
    {
        return array_filter(explode(',', $this->option('only') ?? ''));
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * @param  string  $section
     * @param  string|array  $data
     * @param  string|null  $value
     * @return void
     */
    public static function add(string $section, $data, string $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                self::$data[$section][] = [$key, $value];
            }
        } else {
            self::$data[$section][] = [$data, $value];
        }
    }
}
