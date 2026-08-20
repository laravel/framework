<?php

namespace Illuminate\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class DeleteNotificationModel extends Model
{
    use Notifiable;

    protected $table = 'delete_notification_test_models';

    public $timestamps = false;

    protected $guarded = [];
}
