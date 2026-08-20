<?php

namespace Illuminate\Tests\App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SentMessageUser extends Model
{
    use Notifiable;

    public $timestamps = false;
}
