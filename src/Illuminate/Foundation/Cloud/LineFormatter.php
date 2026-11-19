<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Formatter\LineFormatter as BaseFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Console\Formatter\OutputFormatter;

class LineFormatter extends BaseFormatter
{
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
    public function format(LogRecord $record): string
    {
        $formatted = parent::format($record);

        return "\n  ".$this->level($record->level).' '.$formatted."\n";
    }

    protected function level(Level $level): string
    {
        $style = match ($level) {
            Level::Info, Level::Notice => 'fg=white;bg=blue',
            Level::Warning => 'fg=black;bg=yellow',
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => 'fg=white;bg=red',
            default => 'fg=white;bg=gray',
        };

        return (new OutputFormatter(decorated: true))->format("<{$style}> {$level->getName()} </>");
    }
}
