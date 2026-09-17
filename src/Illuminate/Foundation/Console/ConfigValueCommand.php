<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:value')]
class ConfigValueCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'config:value {config : The configuration file or key to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all of the values for a given configuration file or key, unformatted for programmatic use';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = $this->argument('config');

        if (!config()->has($config)) {
            $this->fail("Configuration file or key {$config} does not exist.");
        }

        $this->render($config);

        return self::SUCCESS;
    }

    /**
     * Render the configuration values.
     *
     * @param  string $name
     * @return void
     */
    public function render($name)
    {
        $data = config($name);

        if (!is_array($data)) {
            $this->line($this->formatValue($data));

            return;
        }

        foreach (Arr::dot($data) as $key => $value) {
            $this->line("{$key}={$this->formatValue($value)}");
        }
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
            is_bool($value)    => $value ? 'true' : 'false',
            is_null($value)    => 'null',
            is_numeric($value) => $value,
            is_array($value)   => '[]',
            is_object($value)  => get_class($value),
            is_string($value)  => $value,
            default            => print_r($value, true),
        };
    }
}
