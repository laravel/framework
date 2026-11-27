<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Formatter\LineFormatter as BaseFormatter;
use Symfony\Component\Console\Formatter\OutputFormatter;

class LineFormatter extends BaseFormatter
{
    /**
     * Create a new line formatter instance.
     */
    public function __construct()
    {
        parent::__construct(
            format: "%message% %context% %extra%\n",
            ignoreEmptyContextAndExtra: true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $formatted = parent::format($record);

        return "\n  ".$this->level($record['level_name']).' '.$formatted."\n";
    }

    /**
     * Get the styled console label for the given log level.
     *
     * @param  string  $level
     * @return string
     */
    protected function level(string $level): string
    {
        $style = match ($level) {
            'INFO', 'NOTICE' => 'fg=white;bg=blue',
            'WARNING' => 'fg=black;bg=yellow',
            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'fg=white;bg=red',
            default => 'fg=white;bg=gray',
        };

        return (new OutputFormatter(decorated: true))->format("<{$style}> {$level} </>");
    }
}
