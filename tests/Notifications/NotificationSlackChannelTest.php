<?php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class NotificationSlackChannelTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testCorrectPayloadIsSentToSlack()
    {
        $notification = new NotificationSlackChannelTestNotification;
        $notifiable = new NotificationSlackChannelTestNotifiable;

        $channel = new Illuminate\Notifications\Channels\SlackWebhookChannel(
            $http = Mockery::mock('GuzzleHttp\Client')
        );

        $http->shouldReceive('post')->with('url', [
            'json' => [
                'text' => 'Content',
                'attachments' => [
                    [
                        'title' => 'Laravel',
                        'title_link' => 'https://laravel.com',
                        'text' => 'Attachment Content',
                        'fields' => [
                            [
                                'title' => 'Project',
                                'value' => 'Laravel',
                                'short' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $channel->send($notifiable, $notification);
    }
}

class NotificationSlackChannelTestNotifiable
{
    use Illuminate\Notifications\Notifiable;

    public function routeNotificationForSlack()
    {
        return 'url';
    }
}

class NotificationSlackChannelTestNotification extends Notification
{
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
                    ->content('Content')
                    ->attachment(function ($attachment) {
                        $attachment->title('Laravel', 'https://laravel.com')
                                   ->content('Attachment Content')
                                   ->fields([
                                        'Project' => 'Laravel',
                                    ]);
                    });
    }
}
