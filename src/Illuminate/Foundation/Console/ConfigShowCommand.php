<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

#[AsCommand(name: 'config:show')]
class ConfigShowCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'config:show {config : The config to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = $this->argument('config');
        $data = config($config);

        if (! $data) {
            $this->components->error("Config `{$config}` does not exist.");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->display($config, $data);
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Render the config information.
     *
     * @param  string  $config
     * @param  mixed  $data
     * @return void
     */
    public function display($config, $data)
    {
        if (! is_array($data)) {
            $this->title($config, $this->formatValue($data));
            return;
        }

        $this->title($config);

        foreach (Arr::dot($data) as $key => $value) {
            $this->components->twoColumnDetail(
                $this->formatKey($key),
                $this->formatValue($value)
            );
        }
    }

    /**
     * Renders the title.
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
     * Formats the config key.
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
     * Formats the config value.
     *
     * @param  string  $value
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
