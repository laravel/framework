<?php

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;

use function PHPStan\Testing\assertType;

class CustomNotification extends DatabaseNotification
{
    /**
     * @param  array<int, CustomNotification>  $models
     */
    public function newCollection(array $models = []): CustomNotificationCollection
    {
        return new CustomNotificationCollection($models);
    }
}

/**
 * @extends DatabaseNotificationCollection<int, CustomNotification>
 */
class CustomNotificationCollection extends DatabaseNotificationCollection
{
}

$databaseNotificationsCollection = DatabaseNotification::all();
assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Notifications\DatabaseNotification>', $databaseNotificationsCollection);

$customNotificationsCollection = CustomNotification::all();
assertType('Illuminate\Database\Eloquent\Collection<int, CustomNotification>', $customNotificationsCollection);
