<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notifiable;
use PHPUnit\Framework\TestCase;

class NotifiableTest extends TestCase
{
    public function testGetDrivers()
    {
        $anonymous = new AnonymousNotifiable;
        $anonymous->route('slack', '#laravel');
        $anonymous->route('mail', 'taylor@laravel.com');
        $anonymous->route('foo', 'bar');
        $anonymous->route('foobar', 'baz');
        $this->assertSame(['slack', 'mail', 'foo', 'foobar'], $anonymous->getDrivers());

        $notifiable = new NotifiableTestInstance;
        $this->assertSame(['slack', 'foo', 'foobar', 'mail', 'database'], $notifiable->getDrivers());
    }
}

class NotifiableTestInstance
{
    use Notifiable;

    public function routeNotificationForSlack(): string
    {
        return '#laravel';
    }

    public function routeNotificationForFoo(): string
    {
        return 'bar';
    }

    public function routeNotificationForFooBar(): string
    {
        return 'baz';
    }
}
