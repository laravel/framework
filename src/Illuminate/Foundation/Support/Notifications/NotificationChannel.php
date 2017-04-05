<?php

namespace Illuminate\Foundation\Support\Notifications;

use Illuminate\Contracts\Notifications\Channels\Dispatcher;
use Illuminate\Contracts\Notifications\Channels\Factory;

/**
 * Class NotificationChannel
 */
abstract class NotificationChannel implements Factory, Dispatcher {}