<?php namespace Illuminate\Contracts\Validation;

use RuntimeException;
use Illuminate\Contracts\Support\MessageProvider;

class ValidationException extends RuntimeException {

	/**
	 * The message provider implementation.
	 *
	 * @var MessageProvider
	 */
	protected $provider;

	/**
	 * Create a new validation exception instance.
	 *
	 * @param  MessageProvider  $provider
	 * @return void
	 */
	public function __construct(MessageProvider $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Get the validation error message provider.
	 *
	 * @return MessagesProvider
	 */
	public function errors()
	{
		return $this->provider->getMessageBag();
	}

	/**
	 * Get the validation error message provider.
	 *
	 * @return MessagesProvider
	 */
	public function getMessageProvider()
	{
		return $this->provider;
	}

}
