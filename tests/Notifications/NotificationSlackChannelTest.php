<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class NotificationSlackChannelTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $payload
     */
    protected function validatePayload($notification, $payload)
    {
        $notifiable = new NotificationSlackChannelTestNotifiable;

        $channel = new \Illuminate\Notifications\Channels\SlackWebhookChannel(
            $http = Mockery::mock('GuzzleHttp\Client')
        );

        $http->shouldReceive('post')->with('url', $payload);

        $channel->send($notifiable, $notification);
    }

    public function testCorrectPayloadIsSentToSlack()
    {
        $this->validatePayload(
            new NotificationSlackChannelTestNotification,
            [
                'json' => [
                    'username' => 'Ghostbot',
                    'icon_emoji' => ':ghost:',
                    'channel' => '#ghost-talk',
                    'text' => 'Content',
                    'attachments' => [
                        [
                            'title' => 'Laravel',
                            'title_link' => 'https://laravel.com',
                            'text' => 'Attachment Content',
                            'fallback' => 'Attachment Fallback',
                            'fields' => [
                                [
                                    'title' => 'Project',
                                    'value' => 'Laravel',
                                    'short' => true,
                                ],
                            ],
                            'mrkdwn_in' => ['text'],
                            'footer' => 'Laravel',
                            'footer_icon' => 'https://laravel.com/fake.png',
                            'ts' => 1234567890,
                        ],
                    ],
                ],
            ]
        );
    }

    public function testCorrectPayloadIsSentToSlackWithImageIcon()
    {
        $this->validatePayload(
            new NotificationSlackChannelTestNotificationWithImageIcon,
            [
                'json' => [
                    'username' => 'Ghostbot',
                    'icon_url' => 'http://example.com/image.png',
                    'channel' => '#ghost-talk',
                    'text' => 'Content',
                    'attachments' => [
                        [
                            'title' => 'Laravel',
                            'title_link' => 'https://laravel.com',
                            'text' => 'Attachment Content',
                            'fallback' => 'Attachment Fallback',
                            'fields' => [
                                [
                                    'title' => 'Project',
                                    'value' => 'Laravel',
                                    'short' => true,
                                ],
                            ],
                            'mrkdwn_in' => ['text'],
                            'footer' => 'Laravel',
                            'footer_icon' => 'https://laravel.com/fake.png',
                            'ts' => 1234567890,
                        ],
                    ],
                ],
            ]
        );
    }

    public function testCorrectPayloadWithoutOptionalFieldsIsSentToSlack()
    {
        $this->validatePayload(
            new NotificationSlackChannelWithoutOptionalFieldsTestNotification,
            [
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
            ]
        );
    }

    public function testCorrectPayloadWithAttachmentFieldBuilderIsSentToSlack()
    {
        $this->validatePayload(
            new NotificationSlackChannelWithAttachmentFieldBuilderTestNotification,
            [
                'json' => [
                    'text' => 'Content',
                    'attachments' => [
                        [
                            'title' => 'Laravel',
                            'text' => 'Attachment Content',
                            'title_link' => 'https://laravel.com',
                            'fields' => [
                                [
                                    'title' => 'Project',
                                    'value' => 'Laravel',
                                    'short' => true,
                                ],
                                [
                                    'title' => 'Special powers',
                                    'value' => 'Zonda',
                                    'short' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}

class NotificationSlackChannelTestNotifiable
{
    use \Illuminate\Notifications\Notifiable;

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
                    ->from('Ghostbot', ':ghost:')
                    ->to('#ghost-talk')
                    ->content('Content')
                    ->attachment(function ($attachment) {
                        $timestamp = Mockery::mock('Carbon\Carbon');
                        $timestamp->shouldReceive('getTimestamp')->andReturn(1234567890);
                        $attachment->title('Laravel', 'https://laravel.com')
                                   ->content('Attachment Content')
                                   ->fallback('Attachment Fallback')
                                   ->fields([
                                        'Project' => 'Laravel',
                                    ])
                                    ->footer('Laravel')
                                    ->footerIcon('https://laravel.com/fake.png')
                                    ->markdown(['text'])
                                    ->timestamp($timestamp);
                    });
    }
}

class NotificationSlackChannelTestNotificationWithImageIcon extends Notification
{
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
                    ->from('Ghostbot')
                    ->image('http://example.com/image.png')
                    ->to('#ghost-talk')
                    ->content('Content')
                    ->attachment(function ($attachment) {
                        $timestamp = Mockery::mock('Carbon\Carbon');
                        $timestamp->shouldReceive('getTimestamp')->andReturn(1234567890);
                        $attachment->title('Laravel', 'https://laravel.com')
                                   ->content('Attachment Content')
                                   ->fallback('Attachment Fallback')
                                   ->fields([
                                        'Project' => 'Laravel',
                                    ])
                                    ->footer('Laravel')
                                    ->footerIcon('https://laravel.com/fake.png')
                                    ->markdown(['text'])
                                    ->timestamp($timestamp);
                    });
    }
}

class NotificationSlackChannelWithoutOptionalFieldsTestNotification extends Notification
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

class NotificationSlackChannelWithAttachmentFieldBuilderTestNotification extends Notification
{
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->content('Content')
            ->attachment(function ($attachment) {
                $attachment->title('Laravel', 'https://laravel.com')
                    ->content('Attachment Content')
                    ->field('Project', 'Laravel')
                    ->field(function ($attachmentField) {
                        $attachmentField
                            ->title('Special powers')
                            ->content('Zonda')
                            ->long();
                    });
            });
    }
}
