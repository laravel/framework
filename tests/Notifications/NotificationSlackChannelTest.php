<?php

use Illuminate\Notifications\Channels\Notification;

class NotificationSlackChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testSmsIsSentViaNexmo()
    {
        $notification = new Notification([
            $notifiable = new NotificationSlackChannelTestNotifiable,
        ]);

        $notification->subject = 'Subject';
        $notification->level = 'success';
        $notification->introLines = ['line 1'];
        $notification->actionText = 'Text';
        $notification->actionUrl = 'url';
        $notification->outroLines = ['line 2'];

        $channel = new Illuminate\Notifications\Channels\SlackWebhookChannel(
            $http = Mockery::mock('GuzzleHttp\Client')
        );

        $http->shouldReceive('post')->with('url', [
            'json' => [
                'attachments' => [
                    [
                        'color' => 'good',
                        'title' => 'Subject',
                        'title_link' => 'url',
                        'text' => 'line 1

<url|Text>

line 2',
                    ],
                ],
            ],
        ]);

        $channel->send($notification);
    }
}

class NotificationSlackChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;
    public $phone_number = '5555555555';

    public function routeNotificationForSlack()
    {
        return 'url';
    }
}
