<?php

use Orchestra\Testbench\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\InstantNotifiable;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * @group integration
 */
class SendingNotificationsViaInstantNotifiableTest extends TestCase
{
    public $mailer;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }

    public function test_mail_is_sent()
    {
        $notifiable = (new InstantNotifiable())
            ->addRoute('enzo', 'testchannel')
            ->addRoute('enzo@deepblue.com', 'anothertestchannel');

        NotificationFacade::send(
            $notifiable,
            new TestMailNotificationForInstantNotifiable()
        );

        $this->assertEquals([
            'enzo', 'enzo@deepblue.com',
        ], $_SERVER['__notifiable.route']);
    }
}

class TestMailNotificationForInstantNotifiable extends Notification
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
