<?php

use Illuminate\Notifications\Notification;

class NotificationTwitterChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testTweetIsSentViaTwitter()
    {
        $notification = new Notification;
        $notifiables = collect([
            $notifiable = new NotificationTwitterChannelTestNotifiable,
        ]);

        $notification->introLines = ['line 1'];
        $notification->actionText = 'Text';
        $notification->actionUrl = 'url';
        $notification->outroLines = ['line 2'];

        $channel = new Illuminate\Notifications\Channels\TwitterChannel(
            $twitter = Mockery::mock(Codebird::class)
        );

        $twitter->shouldReceive('statuses_update')->with([
            'status' => 'line1<url|Text>line2',
        ]);

        $channel->send($notifiables, $notification);
    }
}

class NotificationTwitterChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public function routeNotificationForTwitter()
    {
        return 'twitter';
    }
}
