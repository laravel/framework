<?php

namespace Illuminate\Foundation\Benchmark\Renderers;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Foundation\BenchmarkRenderer;

class ConsoleRenderer implements BenchmarkRenderer
{
    use Concerns\InspectsClosures, Concerns\Terminatable;

    /**
     * The console output implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Creates a new Renderer instance.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function render($results, $repetitions)
    {
        $components = new Factory($this->output);

        $components->info(sprintf(
            'Benchmarking [%s] %s using [%s] %s.',
            $results->count(),
            str('callback')->plural($results->count()),
            $repetitions,
            str('repetition')->plural($repetitions),
        ));

        $averages = $results->map(fn ($result) => $result->average)->toArray();

        $fasterIndex = min(array_keys($averages, min($averages)));

        $results->each(function ($result, $index) use ($results, $components, $fasterIndex) {
            $average = number_format($result->average / 1000000, 3).'ms';

            if (! is_string($key = $result->key)) {
                $key = $this->getCodeDescription($result->callback);
            }

            $key = sprintf('<fg=gray>%s</>', $key);

            if ($results->count() > 1) {
                $key = sprintf('[%s] %s', $index + 1, $key);
            }

            $color = $index == $fasterIndex && $results->count() > 1 ? 'green' : 'default';

            $components->twoColumnDetail($key, sprintf('<fg=%s;options=bold>%s</>', $color, $average));
        });

        $components->newLine();

        $this->terminate();
    }
}
