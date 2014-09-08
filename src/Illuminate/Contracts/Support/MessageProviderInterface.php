<?php namespace Illuminate\Contracts\Support;

interface MessageProviderInterface {

	/**
	 * Get the messages for the instance.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getMessageBag();

}
