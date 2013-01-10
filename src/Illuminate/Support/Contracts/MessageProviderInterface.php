<?php namespace Illuminate\Support\Contracts;

interface MessageProviderInterface {

	/**
	 * Get the messages for the instance.
	 *
	 * @return ILluminate\Support\MessageBag
	 */
	public function getMessageBag();

}