<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificationWithSerialization extends NotificationStub implements ShouldQueue
{
    use Queueable;

    public function __construct(public $value)
    {
    }

    public function __serialize(): array
    {
        return ['value' => $this->value.'-serialized'];
    }

    public function __unserialize(array $data): void
    {
        $this->value = $data['value'].'-unserialized';
    }
}
