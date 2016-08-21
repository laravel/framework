<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Notification;

class SMSChannel
{
	/**
	 * The HTTP client instance.
	 *
	 * @var \GuzzleHttp\Client
	 */
	protected $http;
	/**
	 * Create a new SMS channel instance.
	 *
	 * @param  \GuzzleHttp\Client  $http
	 * @return void
	 */
	public function __construct(HttpClient $http)
	{
		$this->http = $http;
	}
	
	/**
	 * Send the given notification.
	 *
	 * @param  mixed  $notifiable
	 * @param  \Illuminate\Notifications\Notification  $notification
	 * @return void
	 */
	public function send($notifiable, Notification $notification)
	{
		$message = $notification->toSMS($notifiable);
		$this->http->post(env('SMS_URL') . '/send/sms', [
			'json' => [
				'key' => env('SMS_KEY'),
				'to' => $message->to,
				'message' => $message->content
			]
		]);
	}
}
