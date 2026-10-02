<?php

namespace Illuminate\Foundation\Cloud;

use Illuminate\Container\Container;
use Monolog\Formatter\JsonFormatter as BaseFormatter;
use Monolog\LogRecord;

class JsonFormatter extends BaseFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function normalizeRecord(LogRecord $record): array
    {
        $normalized = parent::normalizeRecord($record);

        $app = Container::getInstance();

        if ($app->bound('request')) {
            $requestId = $app->make('request')->header('Cloud-Request-ID');

            if ($requestId !== null) {
                $normalized['cloud_request_id'] = $requestId;
            }
        }

        return $normalized;
    }
}
