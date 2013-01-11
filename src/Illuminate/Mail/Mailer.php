<?php namespace Illuminate\Mail;

use Closure;
use Swift_Mailer;
use Swift_Message;
use Illuminate\Log\Writer;
use Illuminate\View\Environment;
use Illuminate\Container\Container;

class Mailer {

	/**
	 * The view environment instance.
	 *
	 * @var Illuminate\View\Environment
	 */
	protected $views;

	/**
	 * The Swift Mailer instance.
	 *
	 * @var Swift_Mailer
	 */
	protected $swift;

	/**
	 * The global from address and name.
	 *
	 * @var array
	 */
	protected $from;

	/**
	 * The log writer instance.
	 *
	 * @var Illuminate\Log\Writer
	 */
	protected $logger;

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Indicates if the actual sending is disabled.
	 *
	 * @var bool
	 */
	protected $pretending = false;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param  Illuminate\View\Environment  $views
	 * @param  Swift_Mailer  $swift
	 * @return void
	 */
	public function __construct(Environment $views, Swift_Mailer $swift)
	{
		$this->views = $views;
		$this->swift = $swift;
	}

	/**
	 * Set the global from address and name.
	 *
	 * @param  string  $address
	 * @param  string  $name
	 * @return void
	 */
	public function alwaysFrom($address, $name = null)
	{
		$this->from = compact('address', 'name');
	}

	/**
	 * Send a new message using a view.
	 *
	 * @param  string|array    $view
	 * @param  array           $data
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function send($view, array $data, $callback)
	{
		if (is_array($view)) list($view, $plain) = $view;

		$data['message'] = $message = $this->createMessage();

		$this->callMessageBuilder($callback, $message);

		// Once we have retrieved the view content for the e-mail we will set the body
		// of this message using the HTML type, which will provide a simple wrapper
		// to creating view based emails that are able to receive arrays of data.
		$content = $this->getView($view, $data);

		$message->setBody($content, 'text/html');

		if (isset($plain))
		{
			$message->addPart($this->getView($plain, $data), 'text/plain');
		}

		return $this->sendSwiftMessage($message->getSwiftMessage());
	}

	/**
	 * Send a Swift Message instance.
	 *
	 * @param  Swift_Message  $message
	 * @return void
	 */
	protected function sendSwiftMessage($message)
	{
		if ( ! $this->pretending)
		{
			return $this->swift->send($message);
		}
		elseif (isset($this->logger))
		{
			$this->logMessage($message);
		}
	}

	/**
	 * Log that a message was sent.
	 *
	 * @param  Swift_Message  $message
	 * @return void
	 */
	protected function logMessage($message)
	{
		$emails = implode(', ', array_keys($message->getTo()));

		$this->logger->info("Pretending to mail message to: {$emails}");
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param  Closure|string  $callback
	 * @param  Illuminate\Mail\Message  $message
	 * @return void
	 */
	protected function callMessageBuilder($callback, $message)
	{
		if ($callback instanceof Closure)
		{
			return call_user_func($callback, $message);
		}
		elseif (is_string($callback))
		{
			return $this->container[$callback]->mail($message);
		}

		throw new \InvalidArgumentException("Callback is not valid.");
	}

	/**
	 * Create a new message instance.
	 *
	 * @return Illuminate\Mail\Message
	 */
	protected function createMessage()
	{
		$message = new Message(new Swift_Message);

		// If a global from address has been specified we will set it on every message
		// instances so the developer does not have to repeat themselves every time
		// they create a new message. We will just go ahead and push the address.
		if (isset($this->from['address']))
		{
			$message->from($this->from['address'], $this->from['name']);
		}

		return $message;
	}

	/**
	 * Render the given view.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @return Illuminate\View\View
	 */
	protected function getView($view, $data)
	{
		return $this->views->make($view, $data)->render();
	}

	/**
	 * Tell the mailer to not really send messages.
	 *
	 * @param  bool  $value
	 * @return void
	 */
	public function pretend($value = true)
	{
		$this->pretending = $value;
	}

	/**
	 * Get the view environment instance.
	 *
	 * @return Illuminate\View\Environment
	 */
	public function getViewEnvironment()
	{
		return $this->views;
	}

	/**
	 * Get the Swift Mailer instance.
	 *
	 * @return Swift_Mailer
	 */
	public function getSwiftMailer()
	{
		return $this->swift;
	}

	/**
	 * Set the Swift Mailer instance.
	 *
	 * @param  Swift_Mailer  $swift
	 * @return void
	 */
	public function setSwiftMailer($swift)
	{
		$this->swift = $swift;
	}

	/**
	 * Set the log writer instance.
	 *
	 * @param  Illuminate\Log\Writer  $logger
	 * @return void
	 */
	public function setLogger(Writer $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}
