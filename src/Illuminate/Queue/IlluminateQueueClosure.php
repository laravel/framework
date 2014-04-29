<?php

use Illuminate\Encryption\Encrypter;

class IlluminateQueueClosure {

	/**
	 * The encrypter instance.
	 *
	 * @var \Illuminate\Encryption\Encrypter  $crypt
	 */
	protected $crypt;

	/**
	 * Create a new queued Closure job.
	 *
	 * @param  \Illuminate\Encryption\Encrypter  $crypt
	 * @return void
	 */
	public function __construct(Encrypter $crypt)
	{
		$this->crypt = $crypt;
	}

	/**
	 * Fire the Closure based queue job.
	 *
	 * @param  \Illuminate\Queue\Jobs\Job  $job
	 * @param  array  $data
	 * @return void
	 */
	public function fire($job, $data)
	{
		$closure = unserialize($this->crypt->decrypt($data['closure']));

		$closure($job);
	}

}
