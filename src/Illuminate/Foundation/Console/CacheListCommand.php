<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'cache:list')]
class CacheListCommand extends Command
{
    protected $name = 'cache:list';

    protected $description = 'List all configured cache stores and their status';

    protected $headers = ['Store', 'Driver', 'Status', 'Error'];

    protected static $terminalWidthResolver;

    protected $statusColors = [
        'OK' => 'green',
        'ERROR' => 'red',
    ];

    public function handle()
    {
        $stores = config('cache.stores');

        if (empty($stores)) {
            return $this->components->error("Your application doesn't have any cache stores configured.");
        }

        $items = $this->getCacheStores();

        if (empty($items)) {
            return $this->components->error("Your application doesn't have any cache stores matching the given criteria.");
        }

        $this->displayStores($items);
    }

    protected function getCacheStores()
    {
        $stores = collect(config('cache.stores'))->map(function ($config, $store) {
            return $this->getCacheStoreInformation($store, $config);
        })->filter()->all();

        if (($sort = $this->option('sort')) !== null) {
            $stores = $this->sortStores($sort, $stores);
        }

        if ($this->option('reverse')) {
            $stores = array_reverse($stores);
        }

        return $this->pluckColumns($stores);
    }

    protected function getCacheStoreInformation($store, $config)
    {
        $status = 'OK';
        $error = null;

        try {
            Cache::store($store)->has('test');
        } catch (\Throwable $th) {
            $status = 'ERROR';
            $error = $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine();
        }

        return $this->filterStore([
            'store' => $store,
            'driver' => $config['driver'],
            'status' => $status,
            'error' => $error,
        ]);
    }

    protected function sortStores($sort, array $stores)
    {
        return Arr::sort($stores, function ($store) use ($sort) {
            return $store[$sort];
        });
    }

    protected function pluckColumns(array $stores)
    {
        return array_map(function ($store) {
            return Arr::only($store, $this->getColumns());
        }, $stores);
    }

    protected function displayStores($stores)
    {
        $stores = collect($stores);

        $this->output->writeln(
            $this->option('json') ? $this->asJson($stores) : $this->forCli($stores)
        );
    }

    protected function filterStore(array $store)
    {
        if (($this->option('store') && ! Str::contains($store['store'], $this->option('store'))) ||
            ($this->option('driver') && ! Str::contains($store['driver'], $this->option('driver'))) ||
            ($this->option('status') && ! Str::contains($store['status'], strtoupper($this->option('status'))))
        ) {
            return;
        }

        return $store;
    }

    protected function getColumns()
    {
        return array_map('strtolower', $this->headers);
    }

    protected function asJson($stores)
    {
        return $stores->values()->toJson();
    }

    protected function forCli($stores)
    {
        $terminalWidth = $this->getTerminalWidth();

        $maxStore = mb_strlen($stores->max('store'));

        $storeCount = $this->determineStoreCountOutput($stores, $terminalWidth);

        return $stores->map(function ($store) use ($maxStore, $terminalWidth) {
            $spaces = str_repeat(' ', max($maxStore + 2 - mb_strlen($store['store']), 0));

            $dots = str_repeat('.', max(
                $terminalWidth - mb_strlen($store['store'] . $spaces . $store['driver']) - 30,
                0
            ));

            $status = sprintf(
                '<fg=%s>%s</>',
                $this->statusColors[$store['status']] ?? 'default',
                $store['status']
            );

            $line = sprintf(
                "  <fg=white;options=bold>%s</> %s<fg=#6C7280>%s</> <fg=#6C7280>%s</> ⇂ %s",
                $store['store'],
                $spaces,
                $dots,
                $store['driver'],
                $status
            );

            if ($store['error']) {
                $line .= sprintf("\n    ⇂ <fg=red>%s</>", $store['error']);
            }

            return $line;
        })
            ->prepend('')
            ->push('')
            ->push($storeCount)
            ->push('')
            ->toArray();
    }

    protected function determineStoreCountOutput($stores, $terminalWidth)
    {
        $storeCountText = 'Showing [' . $stores->count() . '] cache stores';

        $offset = $terminalWidth - mb_strlen($storeCountText) - 2;

        $spaces = str_repeat(' ', $offset);

        return $spaces . '<fg=blue;options=bold>' . $storeCountText . '</>';
    }

    public static function getTerminalWidth()
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    public static function resolveTerminalWidthUsing($resolver)
    {
        static::$terminalWidthResolver = $resolver;
    }

    protected function getOptions()
    {
        return [
            ['json', null, InputOption::VALUE_NONE, 'Output the cache store list as JSON'],
            ['store', null, InputOption::VALUE_OPTIONAL, 'Filter the stores by name'],
            ['driver', null, InputOption::VALUE_OPTIONAL, 'Filter the stores by driver'],
            ['status', null, InputOption::VALUE_OPTIONAL, 'Filter the stores by status (OK or ERROR)'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the stores'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (store, driver, status) to sort by', 'store'],
        ];
    }
}
