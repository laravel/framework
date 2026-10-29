<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:show')]
class ConfigShowCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'config:show
                    {config : The configuration file or key to show}
                    {--show-sensitive : Reveal sensitive values, which are masked by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all of the values for a given configuration file or key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = $this->argument('config');

        if (! config()->has($config)) {
            $this->fail("Configuration file or key <comment>{$config}</comment> does not exist.");
        }

        $this->newLine();
        $this->render($config);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Render the configuration values.
     *
     * @param  string  $name
     * @return void
     */
    public function render($name)
    {
        $data = config($name);

        if (! is_array($data)) {
            $this->title($name, $this->formatValue($this->maskSensitiveValue($name, $data)));

            return;
        }

        $this->title($name);

        foreach (Arr::dot($data) as $key => $value) {
            $this->components->twoColumnDetail(
                $this->formatKey($key),
                $this->formatValue($this->maskSensitiveValue("{$name}.{$key}", $value))
            );
        }
    }

    /**
     * Render the title.
     *
     * @param  string  $title
     * @param  string|null  $subtitle
     * @return void
     */
    public function title($title, $subtitle = null)
    {
        $this->components->twoColumnDetail(
            "<fg=green;options=bold>{$title}</>",
            $subtitle,
        );
    }

    /**
     * Format the given configuration key.
     *
     * @param  string  $key
     * @return string
     */
    protected function formatKey($key)
    {
        return preg_replace_callback(
            '/(.*)\.(.*)$/', fn ($matches) => sprintf(
                '<fg=gray>%s ⇁</> %s',
                str_replace('.', ' ⇁ ', $matches[1]),
                $matches[2]
            ), $key
        );
    }

    /**
     * Mask the given configuration value if its key or URL credentials mark it as sensitive.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function maskSensitiveValue($key, $value)
    {
        if ($this->option('show-sensitive') || ! is_string($value) || $value === '') {
            return $value;
        }

        // List items such as app.previous_keys.0 are judged by the name of their parent key.
        $name = Arr::last(explode('.', $key), fn ($segment) => ! is_numeric($segment), '');

        if (preg_match('/key|secret|pass|token|credential|private|dsn|sasl/i', $name)) {
            return '********';
        }

        $password = parse_url($value, PHP_URL_PASS);

        if (is_string($password) && $password !== '') {
            $value = str_replace(":{$password}@", ':********@', $value);
        }

        return preg_replace('/([?&]password=)[^&#]+/', '$1********', $value);
    }

    /**
     * Format the given configuration value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatValue($value)
    {
        return match (true) {
            is_bool($value) => sprintf('<fg=#ef8414;options=bold>%s</>', $value ? 'true' : 'false'),
            is_null($value) => '<fg=#ef8414;options=bold>null</>',
            is_numeric($value) => "<fg=#ef8414;options=bold>{$value}</>",
            is_array($value) => '[]',
            is_object($value) => get_class($value),
            is_string($value) => $value,
            default => print_r($value, true),
        };
    }
}
