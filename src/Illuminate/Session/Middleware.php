<?php namespace Illuminate\Session;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Middleware implements HttpKernelInterface {

	/**
	 * The wrapped kernel implementation.
	 *
	 * @var \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	protected $app;

	/**
	 * The session manager.
	 *
	 * @var \Illuminate\Session\SessionManager
	 */
	protected $manager;

	/**
	 * Create a new session middleware.
	 *
	 * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
	 * @param  \Illuminate\Session\SessionManager  $manager
	 * @return void
	 */
	public function __construct(HttpKernelInterface $app, SessionManager $manager)
	{
		$this->app = $app;
		$this->manager = $manager;
	}

	/**
	 * Handle the given request and get the response.
	 *
	 * @implements HttpKernelInterface::handle
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  int   $type
	 * @param  bool  $catch
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		if ($this->sessionConfigured())
		{
			with($session = $this->getDriver())->setId($request->cookies->get($session->getName()));

			if ($session->handlerNeedsRequest())
			{
				$session->registerRequestWithHandler($request);
			}

			$session->start();
		}

		$response = $this->app->handle($request, $type, $catch);

		if ($this->sessionConfigured())
		{
			$session->save();

			$this->collectGarbage($session);

			$this->setCookieOnResponse($response, $session);
		}

		return $response;
	}

	/**
	 * Set the session cookie on the application response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @param  \Symfony\Component\HttpFoundation\Session\SessionInterface  $session
	 * @return void
	 */
	protected function setCookieOnResponse(Response $response, SessionInterface $session)
	{
		$config = $this->manager->getSessionConfig();

		if ( ! in_array($config['driver'], array(null, 'array')))
		{
			$response->headers->setCookie(new Cookie(
				$session->getName(), $session->getId(), $this->getCookieLifetime($config),
				$config['path'], $config['domain'], false, true
			));
		}
	}

	/**
	 * Remove the garbage from the session if necessary.
	 *
	 * @param  \Illuminate\Session\SessionInterface  $session
	 * @return void
	 */
	protected function collectGarbage(SessionInterface $session)
	{
		$config = $this->manager->getSessionConfig();

		if (mt_rand(1, $config['lottery'][1]) <= $config['lottery'][0])
		{
			$session->getHandler()->gc($this->getLifetimeSeconds());
		}
	}

	/**
	 * Determine if a session driver has been configured.
	 *
	 * @return bool
	 */
	protected function sessionConfigured()
	{
		$config = $this->manager->getSessionConfig();

		return ! is_null($config['driver']);
	}

	/**
	 * Get the session lifetime in seconds.
	 *
	 *
	 */
	protected function getLifetimeSeconds()
	{
		return array_get($this->manager->getSessionConfig(), 'lifetime') * 60;
	}

	/**
	 * Get the cookie lifetime in seconds.
	 *
	 * @param  array  $config
	 * @return int
	 */
	protected function getCookieLifetime(array $config)
	{
		return $config['expire_on_close'] ? 0 : Carbon::now()->addMinutes($config['lifetime']);
	}

	/**
	 * Get the session implementation from the manager.
	 *
	 * @return \Illuminate\Session\SessionInterface
	 */
	public function getDriver()
	{
		return $this->manager->driver();
	}

}