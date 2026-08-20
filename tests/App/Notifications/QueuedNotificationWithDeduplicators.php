<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QueuedNotificationWithDeduplicators extends Notification implements ShouldQueue
{
    use Queueable;

    public $deduplicatorResults = [
        'test' => 'deduplication-id-1',
        'test2' => 'deduplication-id-2',
    ];

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function withDeduplicators($notifiable, $channel)
    {
        return match ($channel) {
            'test' => fn ($payload, $queue) => $this->deduplicatorResults['test'],
            'test2' => fn ($payload, $queue) => $this->deduplicatorResults['test2'],
            default => null,
        };
    }
}
