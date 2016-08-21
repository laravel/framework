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
	 * The SMS Url config.
	 *
	 * @var SMS Url
	 */
	protected $url;
	
	/**
	 * The SMS Key config.
	 *
	 * @var SMS Key
	 */
	protected $key;
	
	/**
	 * Create a new SMS channel instance.
	 *
	 * @param  \GuzzleHttp\Client  $http
	 * @return void
	 */
	public function __construct(HttpClient $http, $key, $url)
	{
		$this->http = $http;
		$this->key  = $key;
		$this->url  = $url;
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
		$this->http->post($this->url . '/send/sms', [
			'json' => [
				'key' => $this->key,
				'to' => $message->to,
				'message' => $message->content
			]
		]);
	}
}
