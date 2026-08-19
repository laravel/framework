<?php

namespace Illuminate\Tests\App\Models\Broadcasting;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;

class TestEloquentBroadcastUserWithSpecificBroadcastPayload extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    public function broadcastWith($event)
    {
        switch ($event) {
            case 'created':
                return ['foo' => 'bar'];
        }
    }
}
