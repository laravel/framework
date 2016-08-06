<?php

use Illuminate\Notifications\Notification;

class NotificationSlackChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testCorrectPayloadIsSentToSlack()
    {
        $notification = new NotificationSlackChannelTestNotification;
        $notifiables = collect([
            $notifiable = new NotificationSlackChannelTestNotifiable,
        ]);

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

        $channel->send($notifiables, $notification);
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

class NotificationSlackChannelTestNotification extends Notification
{
    public function message($notifiable)
    {
        return $this->subject('Subject')
                    ->success()
                    ->line('line 1')
                    ->action('Text', 'url')
                    ->line('line 2');
    }
}
