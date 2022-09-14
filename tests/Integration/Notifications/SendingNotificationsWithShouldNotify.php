<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Contracts\Notifications\ShouldNotify;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Orchestra\Testbench\TestCase;

class SendingNotificationsWithShouldNotify extends TestCase
{
    public function testNotificationIsSentWhenImplementingShouldNotify()
    {
        $fake = NotificationFacade::fake();
        $this->assertInstanceOf(NotificationFake::class, $fake);

        $model = new FakeModel;
        $model->user = new AnonymousNotifiable;

        Event::dispatch(new FakeModelEventWithShouldNotify($model));

        NotificationFacade::assertSentTo(new $model->user, FakeModelEventWithShouldNotify::class);
    }
}

class FakeModel
{
    public $user;
}

class FakeModelEventWithShouldNotify extends Notification implements ShouldNotify
{
    public $user;

    public function __construct($model)
    {
        $this->user = $model->user;
    }

    public function notifyTo()
    {
        return $this->user;
    }

    public function via($notifiable)
    {
        return [];
    }
}
