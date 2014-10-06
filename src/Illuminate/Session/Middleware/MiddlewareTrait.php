<?php namespace Illuminate\Session\Middleware;

trait MiddlewareTrait {

	/**
	 * Determine if a session driver has been configured.
	 *
	 * @return bool
	 */
	protected function sessionConfigured()
	{
		return ! is_null(array_get($this->manager->getSessionConfig(), 'driver'));
	}

	/**
	 * Determine if the configured session driver is persistent.
	 *
	 * @param  array|null  $config
	 * @return bool
	 */
	protected function sessionIsPersistent(array $config = null)
	{
		// Some session drivers are not persistent, such as the test array driver or even
		// when the developer don't have a session driver configured at all, which the
		// session cookies will not need to get set on any responses in those cases.
		$config = $config ?: $this->manager->getSessionConfig();

		return ! in_array($config['driver'], array(null, 'array'));
	}

}
