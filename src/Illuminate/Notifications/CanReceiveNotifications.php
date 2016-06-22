<?php

namespace Illuminate\Notifications;

trait CanReceiveNotifications
{
    use HasDatabaseNotifications, RoutesNotifications;
}
