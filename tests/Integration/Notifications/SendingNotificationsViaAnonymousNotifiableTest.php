<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class SendingNotificationsViaAnonymousNotifiableTest extends TestCase
{
    public $mailer;

    protected function setUp(): void
    {
        if (\PHP_VERSION_ID >= 80100) {
            $this->markTestSkipped('Test failing in PHP 8.1');
        }

        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }

    public function testMailIsSent()
    {
        $notifiable = (new AnonymousNotifiable)
            ->route('testchannel', 'enzo')
            ->route('anothertestchannel', 'enzo@deepblue.com');

        NotificationFacade::send(
            $notifiable,
            new TestMailNotificationForAnonymousNotifiable
        );

        $this->assertEquals([
            'enzo', 'enzo@deepblue.com',
        ], $_SERVER['__notifiable.route']);
    }

    public function testFaking()
    {
        $fake = NotificationFacade::fake();

        $this->assertInstanceOf(NotificationFake::class, $fake);

        $notifiable = (new AnonymousNotifiable)
            ->route('testchannel', 'enzo')
            ->route('anothertestchannel', 'enzo@deepblue.com');

        NotificationFacade::locale('it')->send(
            $notifiable,
            new TestMailNotificationForAnonymousNotifiable
        );

        NotificationFacade::assertSentTo(new AnonymousNotifiable, TestMailNotificationForAnonymousNotifiable::class,
            function ($notification, $channels, $notifiable, $locale) {
                return $notifiable->routes['testchannel'] === 'enzo' &&
                    $notifiable->routes['anothertestchannel'] === 'enzo@deepblue.com' &&
                    $locale === 'it';
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
