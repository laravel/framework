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
            "%message% %context% %extra%\n",
            ignoreEmptyContextAndExtra: true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $formatted = parent::format($record);

        return $this->colorize($record->level).' '.$formatted;
    }

    protected function colorize(Level $level): string
    {
        [$background, $foreground] = match ($level) {
            Level::Info, Level::Notice => ['44', '97'],
            Level::Warning => ['43', '30'],
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => ['41', '97'],
            default => ['100', '97'],
        };

        return "\033[{$background};{$foreground}m{$level}\033[0m";
    }
}
