<?php

namespace Illuminate\Tests\Integration\Notifications;

use Orchestra\Testbench\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * @group integration
 */
class SendingNotificationsViaAnonymousNotifiableTest extends TestCase
{
    public $mailer;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }

    public function test_mail_is_sent()
    {
        $notifiable = (new AnonymousNotifiable())
            ->route('testchannel', 'enzo')
            ->route('anothertestchannel', 'enzo@deepblue.com');

        NotificationFacade::send(
            $notifiable,
            new TestMailNotificationForAnonymousNotifiable()
        );

        $this->assertEquals([
            'enzo', 'enzo@deepblue.com',
        ], $_SERVER['__notifiable.route']);
    }

    public function test_faking()
    {
        NotificationFacade::fake();

        $notifiable = (new AnonymousNotifiable())
            ->route('testchannel', 'enzo')
            ->route('anothertestchannel', 'enzo@deepblue.com');

        NotificationFacade::send(
            $notifiable,
            new TestMailNotificationForAnonymousNotifiable()
        );

        NotificationFacade::assertSentTo(new AnonymousNotifiable(), TestMailNotificationForAnonymousNotifiable::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['testchannel'] == 'enzo' && $notifiable->routes['anothertestchannel'] == 'enzo@deepblue.com';
            }
        );
    }
}

class TestMailNotificationForAnonymousNotifiable extends Notification
{
    public function via($notifiable)
    {
        return [TestCustomChannel::class, AnotherTestCustomChannel::class];
    }
}

class TestCustomChannel
{
    public function send($notifiable, $notification)
    {
        $_SERVER['__notifiable.route'][] = $notifiable->routeNotificationFor('testchannel');
    }
}

class AnotherTestCustomChannel
{
    public function send($notifiable, $notification)
    {
        $_SERVER['__notifiable.route'][] = $notifiable->routeNotificationFor('anothertestchannel');
    }
}
