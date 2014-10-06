<?php namespace Illuminate\Session\Middleware;

use Closure;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Routing\Middleware as MiddlewareContract;

class Reader implements MiddlewareContract {

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
		// If a session driver has been configured, we will need to start the session here
		// so that the data is ready for an application. Note that the Laravel sessions
		// do not make use of PHP "native" sessions in any way since they are crappy.
		if ($this->sessionConfigured())
		{
			$session = $this->startSession($request);

			$request->setSession($session);
		}

		return $next($request);
	}

	/**
	 * Start the session for the given request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Illuminate\Session\SessionInterface
	 */
	protected function startSession(Request $request)
	{
		with($session = $this->getSession($request))->setRequestOnHandler($request);

		$session->start();

		return $session;
	}

	/**
	 * Get the session implementation from the manager.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Illuminate\Session\SessionInterface
	 */
	public function getSession(Request $request)
	{
		$session = $this->manager->driver();

		$session->setId($request->cookies->get($session->getName()));

		return $session;
	}

}
