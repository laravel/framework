<?php

namespace Illuminate\Tests\Notifications\Fixtures;

class RecordingChannel
{
    public array $sent = [];

    public mixed $response = null;

    public ?\Throwable $exception = null;

    public function send($notifiable, $notification)
    {
        $this->sent[] = [$notifiable, $notification];

        if ($this->exception) {
            throw $this->exception;
        }

        return $this->response;
    }
}
