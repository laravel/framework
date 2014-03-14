<?php namespace Illuminate\Mail\Transport;

use Swift_Transport;
use Swift_Mime_Message;
use Swift_Events_EventListener;
use Guzzle\Http\Client as HttpClient;

class MailgunTransport implements Swift_Transport {

	/**
	 * The Mailgun API key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * The Mailgun domain.
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * The path where temporary files are written.
	 *
	 * @var string
	 */
	protected $storagePath;

	/**
	 * Create a new Mailgun transport instance.
	 *
	 * @param  string  $key
	 * @param  string  $domain
	 * @param  string  $storagePath
	 * @return void
	 */
	public function __construct($key, $domain, $storagePath = null)
	{
		$this->key = $key;
		$this->domain = $domain;
		$this->storagePath = $storagePath;
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
		$request = $this->getHttpClient()
                        ->post()
                        ->addPostFields(['to' => $this->getTo($message)])
                        ->setAuth('api', $this->key);

		$message = (string) $message;

		file_put_contents(
			$path = $this->getStoragePath().'/'.md5($message), $message
		);

		$request->addPostFile('message', $path)->send();

		@unlink($path);
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerPlugin(Swift_Events_EventListener $plugin)
	{
		//
	}

	/**
	 * Get the "to" payload field for the API request.
	 *
	 * @param  \Swift_Mime_Message  $message
	 * @return array
	 */
	protected function getTo(Swift_Mime_Message $message)
	{
		$formatted = [];

		$contacts = array_merge(
			(array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
		);

		foreach ($contacts as $address => $display)
		{
			$formatted[] = $display ? $display." <$address>" : $address;
		}

		return implode(',', $formatted);
	}

	/**
	 * Get a new HTTP client instance.
	 *
	 * @return \Guzzle\Http\Client
	 */
	protected function getHttpClient()
	{
		return new HttpClient('https://api.mailgun.net/v2/'.$this->domain.'/messages.mime');
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

	/**
	 * Get the domain being used by the transport.
	 *
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Set the domain being used by the transport.
	 *
	 * @param  string  $domain
	 * @return void
	 */
	public function setDomain($domain)
	{
		return $this->domain = $domain;
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	public function getStoragePath()
	{
		return $this->storagePath ?: storage_path().'/meta';
	}

	/**
	 * Set the storage path.
	 *
	 * @param  string  $storagePath
	 * @return void
	 */
	public function setStoragePath($storagePath)
	{
		$this->storagePath = $storagePath;
	}

}