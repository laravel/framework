<?php

namespace Illuminate\Foundation\Cloud;

use Monolog\Handler\SocketHandler as BaseHandler;
use Monolog\LogRecord;
use Throwable;

class SocketHandler extends BaseHandler
{
    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        try {
            parent::write($record);
        } catch (Throwable $e) {
            // PHP keeps a socket's "timed_out" flag set after a write times out, so
            // reusing the socket would fail every later write. Reconnect instead.
            $this->closeSocket();

            throw $e;
        }
    }
}
