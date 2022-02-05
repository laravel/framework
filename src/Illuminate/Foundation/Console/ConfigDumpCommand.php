<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConfigDumpCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'config:dump {id?}';

    /**
     * @var string
     */
    protected $description = 'Dump current config';

    protected Repository $config;

    public function __construct(Repository $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    public function handle(): int
    {
        $rawConfig = $this->argument('id') ? $this->config->get($this->argument('id')) : $this->config->all();

        if (is_array($rawConfig)) {
            $rows = Collection::make(Arr::dot($rawConfig));
            $rows = $rows->map(function ($value, $key) {
                return [
                    $key,
                    is_array($value) ? '' : $value,
                ];
            });

            $this->table(
                ['Key', 'Value'],
                $rows,
            );
        } elseif (is_string($rawConfig)) {
            $this->info($rawConfig);
        }

        return 0;
    }
}
