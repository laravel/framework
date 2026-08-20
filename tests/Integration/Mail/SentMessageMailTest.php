<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Notifications\SentMessageUser;
use Illuminate\Tests\App\Notifications\SentMessageMailNotification;
use Orchestra\Testbench\TestCase;

class SentMessageMailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        Schema::create('sent_message_users', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    protected function beforeRefreshingDatabase()
    {
        Schema::dropIfExists('sent_message_users');
    }

    public function testDispatchesNotificationSent()
    {
        $notificationWasSent = false;

        $user = SentMessageUser::create();

        Event::listen(
            NotificationSent::class,
            function (NotificationSent $notification) use (&$notificationWasSent, $user) {
                $notificationWasSent = true;
                /**
                 * Confirm that NotificationSent can be serialized/unserialized as
                 * will happen if the listener implements ShouldQueue.
                 */
                /** @var NotificationSent $afterSerialization */
                $afterSerialization = unserialize(serialize($notification));

                $this->assertTrue($user->is($afterSerialization->notifiable));

                $this->assertEqualsCanonicalizing($notification->notification, $afterSerialization->notification);
            });

        $user->notify(new SentMessageMailNotification());

        $this->assertTrue($notificationWasSent);
    }
}
