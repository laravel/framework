<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Formatter\LineFormatter as BaseFormatter;
use Monolog\Level;
use Monolog\LogRecord;

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

        return '  '.$this->colorize($record->level).' '.$formatted;
    }

    protected function colorize(Level $level): string
    {
        // Same codes as Termwind's console line: white on blue, black on yellow, white on red.
        [$foreground, $background] = match ($level) {
            Level::Info, Level::Notice => ['37', '44'],
            Level::Warning => ['30', '43'],
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => ['37', '41'],
            default => ['37', '100'],
        };

        return "\033[{$foreground};{$background}m {$level->getName()} \033[39;49m";
    }
}
