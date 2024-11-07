<?php

namespace Illuminate\Notifications;

trait Notifiable
{
    use HasDatabaseNotifications, RoutesNotifications;

    /**
     * Get all the drivers the notifiable supports being routed to.
     *
     * @return array
     */
    public function getDrivers(): array
    {
        return collect(get_class_methods($this))
            ->filter(fn (string $method): bool => str_starts_with($method, 'routeNotificationFor'))
            ->map(fn (string $method): string => str($method)->replace('routeNotificationFor', '')->lower())
            ->merge(['mail', 'database']) // via the RoutesNotifications trait
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
