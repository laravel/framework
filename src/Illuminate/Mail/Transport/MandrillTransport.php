<?php namespace Illuminate\Mail\Transport;

use Swift_Transport;
use GuzzleHttp\Client;
use Swift_Mime_Message;
use Swift_Events_EventListener;

class MandrillTransport implements Swift_Transport {

	/**
	 * The Mandrill API key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Create a new Mandrill transport instance.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __construct($key)
	{
		$this->key = $key;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isStarted()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function stop()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function send(Swift_Mime_Message $message, &$failedRecipients = null)
	{
		$client = $this->getHttpClient();

		return $client->post('https://mandrillapp.com/api/1.0/messages/send-raw.json', [
			'body' => [
				'key' => $this->key,
				'to' => $this->getToAddresses($message),
				'raw_message' => (string) $message,
				'async' => false,
			],
		]);
	}

	/**
	 * Get all the addresses this message should be sent to.
	 *
	 * Note that Mandrill still respects CC, BCC headers in raw message itself.
	 *
	 * @param  Swift_Mime_Message $message
	 * @return array
	 */
	protected function getToAddresses(Swift_Mime_Message $message)
	{
		$to = [];

		if ($message->getTo())
		{
			$to = array_merge($to, array_keys($message->getTo()));
		}

		if ($message->getCc())
		{
			$to = array_merge($to, array_keys($message->getCc()));
		}

		if ($message->getBcc())
		{
			$to = array_merge($to, array_keys($message->getBcc()));
		}

		return $to;
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerPlugin(Swift_Events_EventListener $plugin)
	{
		//
	}

	/**
	 * Get a new HTTP client instance.
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected function getHttpClient()
	{
		return new Client;
	}

	/**
	 * Get the API key being used by the transport.
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Set the API key being used by the transport.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function setKey($key)
	{
		return $this->key = $key;
	}

}
