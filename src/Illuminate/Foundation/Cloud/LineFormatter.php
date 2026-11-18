<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Monolog\Utils;

class LineFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        $message = str_replace(["\r\n", "\r", "\n"], ' ', $record->message);

        $suffix = '';

        if ($record->context !== []) {
            $suffix .= ' '.Utils::jsonEncode($record->context, ignoreErrors: true);
        }

        if ($record->extra !== []) {
            $suffix .= ' '.Utils::jsonEncode($record->extra, ignoreErrors: true);
        }

        return $this->badge($record->level->getName()).' '.$message.$suffix."\n";
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records): string
    {
        return implode('', array_map($this->format(...), $records));
    }

    protected function badge(string $level): string
    {
        // Matches Laravel's console Line component: blue info, yellow warn, red error.
        [$background, $foreground] = match ($level) {
            'INFO', 'NOTICE' => ['44', '97'],
            'WARNING' => ['43', '30'],
            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => ['41', '97'],
            default => ['100', '97'],
        };

        return "\033[{$background};{$foreground}m {$level} \033[0m";
    }
}
