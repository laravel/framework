<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Formatter\LineFormatter as BaseFormatter;
use Monolog\LogRecord;

class LineFormatter extends BaseFormatter
{
    public function __construct()
    {
        parent::__construct(
            "%level_name% %message% %context% %extra%\n",
            ignoreEmptyContextAndExtra: true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $formatted = parent::format($record);

        $level = $record->level->getName();

        return $this->colorize($level).substr($formatted, strlen($level));
    }

    protected function colorize(string $level): string
    {
        // Matches Laravel's console Line component: blue info, yellow warn, red error.
        [$background, $foreground] = match ($level) {
            'INFO', 'NOTICE' => ['44', '97'],
            'WARNING' => ['43', '30'],
            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => ['41', '97'],
            default => ['100', '97'],
        };

        return "\033[{$background};{$foreground}m{$level}\033[0m";
    }
}
