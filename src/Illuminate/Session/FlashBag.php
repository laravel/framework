<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Session\Flash\AutoExpireFlashBag;

class FlashBag extends AutoExpireFlashBag {

	/**
	 * {@inheritdoc}
	 */
	public function set($type, $messages)
	{
		$this->flashes['new'][$type] = array($messages);
	}

}