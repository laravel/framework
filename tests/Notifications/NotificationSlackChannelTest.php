<?php

use Illuminate\Notifications\Message;
use Illuminate\Notifications\Notification;

class NotificationSlackChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testCorrectPayloadIsSentToSlack()
    {
        $notifiables = collect([
            $notifiable = new NotificationSlackChannelTestNotifiable,
        ]);
        $message = new Message($notifiable, new Notification);

        $message->subject = 'Subject';
        $message->success();
        $message->introLines = ['line 1'];
        $message->actionText = 'Text';
        $message->actionUrl = 'url';
        $message->outroLines = ['line 2'];

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

        $channel->send($notifiables, $message);
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
