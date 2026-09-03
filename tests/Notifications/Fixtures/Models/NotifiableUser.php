<?php

namespace Illuminate\Tests\Notifications\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}
