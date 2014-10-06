<?php namespace Illuminate\Session\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Session\SessionManager;
use Illuminate\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Routing\Middleware as MiddlewareContract;

class Writer implements MiddlewareContract {

	use MiddlewareTrait;

	/**
	 * The session manager.
	 *
	 * @var \Illuminate\Session\SessionManager
	 */
	protected $manager;

	/**
	 * Create a new session middleware.
	 *
	 * @param  \Illuminate\Session\SessionManager  $manager
	 * @return void
	 */
	public function __construct(SessionManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);

		// Again, if the session has been configured we will need to close out the session
		// so that the attributes may be persisted to some storage medium. We will also
		// add the session identifier cookie to the application response headers now.
		if ($this->sessionConfigured())
		{
			with($session = $this->getSession())->save();

			$this->collectGarbage($session);

			$this->addCookieToResponse($response, $session);
		}

		return $response;
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

		// Here we will see if this request hits the garbage collection lottery by hitting
		// the odds needed to perform garbage collection on any given request. If we do
		// hit it, we'll call this handler to let it delete all the expired sessions.
		if ($this->configHitsLottery($config))
		{
			$session->getHandler()->gc($this->getSessionLifetimeInSeconds());
		}
	}

	/**
	 * Determine if the configuration odds hit the lottery.
	 *
	 * @param  array  $config
	 * @return bool
	 */
	protected function configHitsLottery(array $config)
	{
		return mt_rand(1, $config['lottery'][1]) <= $config['lottery'][0];
	}

	/**
	 * Add the session cookie to the application response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @param  \Symfony\Component\HttpFoundation\Session\SessionInterface  $session
	 * @return void
	 */
	protected function addCookieToResponse(Response $response, SessionInterface $session)
	{
		if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig()))
		{
			$response->headers->setCookie(new Cookie(
				$session->getName(), $session->getId(), $this->getCookieExpirationDate(),
				$config['path'], $config['domain'], array_get($config, 'secure', false)
			));
		}
	}

	/**
	 * Get the session lifetime in seconds.
	 *
	 * @return int
	 */
	protected function getSessionLifetimeInSeconds()
	{
		return array_get($this->manager->getSessionConfig(), 'lifetime') * 60;
	}

	/**
	 * Get the cookie lifetime in seconds.
	 *
	 * @return int
	 */
	protected function getCookieExpirationDate()
	{
		$config = $this->manager->getSessionConfig();

		return $config['expire_on_close'] ? 0 : Carbon::now()->addMinutes($config['lifetime']);
	}

	/**
	 * Get the session implementation from the manager.
	 *
	 * @return \Illuminate\Session\SessionInterface
	 */
	public function getSession()
	{
		return $this->manager->driver();
	}

}
