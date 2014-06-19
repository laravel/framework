<?php namespace Illuminate\Auth;

use Illuminate\Cookie\CookieJar;
use Illuminate\Events\Dispatcher;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Session\Store as SessionStore;
use Symfony\Component\HttpFoundation\Response;

class Guard {

	/**
	 * The currently authenticated user.
	 *
	 * @var UserInterface
	 */
	protected $user;

	/**
	 * The user provider implementation.
	 *
	 * @var \Illuminate\Auth\UserProviderInterface
	 */
	protected $provider;

	/**
	 * The session store used by the guard.
	 *
	 * @var \Illuminate\Session\Store
	 */
	protected $session;

	/**
	 * The Illuminate cookie creator service.
	 *
	 * @var \Illuminate\Cookie\CookieJar
	 */
	protected $cookie;

	/**
	 * The request instance.
	 *
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Indicates if the logout method has been called.
	 *
	 * @var bool
	 */
	protected $loggedOut = false;

	/**
	 * Create a new authentication guard.
	 *
	 * @param  \Illuminate\Auth\UserProviderInterface  $provider
	 * @param  \Illuminate\Session\Store  $session
	 * @return void
	 */
	public function __construct(UserProviderInterface $provider,
                                SessionStore $session)
	{
		$this->session = $session;
		$this->provider = $provider;
	}

	/**
	 * Determine if the current user is authenticated.
	 *
	 * @return bool
	 */
	public function check()
	{
		return ! is_null($this->user());
	}

	/**
	 * Determine if the current user is a guest.
	 *
	 * @return bool
	 */
	public function guest()
	{
		return ! $this->check();
	}

	/**
	 * Get the currently authenticated user.
	 *
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function user()
	{
		if ($this->loggedOut) return;

		// If we have already retrieved the user for the current request we can just
		// return it back immediately. We do not want to pull the user data every
		// request into the method becaue that would tremendously slow the app.
		if ( ! is_null($this->user))
		{
			return $this->user;
		}

		$id = $this->session->get($this->getName());

		// First we will try to load the user using the identifier in the session if
		// one exists. Otherwise we will check for a "remember me" cookie in this
		// request, and if one exists, attempt to retrieve the user using that.
		$user = null;

		if ( ! is_null($id))
		{
			$user = $this->provider->retrieveByID($id);
		}

		// If the user is null, but we decrypt a "recaller" cookie we can attempt to
		// pull the user data on that cookie which serves as a remember cookie on
		// the application. Once we have a user we can return it to the caller.
		$recaller = $this->getRecaller();

		if (is_null($user) and ! is_null($recaller))
		{
			$user = $this->provider->retrieveByID($recaller);
		}

		return $this->user = $user;
	}

	/**
	 * Get the decrypted recaller cookie for the request.
	 *
	 * @return string|null
	 */
	protected function getRecaller()
	{
		if (isset($this->cookie))
		{
			return $this->getCookieJar()->get($this->getRecallerName());
		}
	}

	/**
	 * Log a user into the application without sessions or cookies.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function once(array $credentials = array())
	{
		if ($this->validate($credentials))
		{
			$this->setUser($this->provider->retrieveByCredentials($credentials));

			return true;
		}

		return false;
	}

	/**
	 * Validate a user's credentials.
	 *
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validate(array $credentials = array())
	{
		return $this->attempt($credentials, false, false);
	}

	/**
	 * Attempt to authenticate using HTTP Basic Auth.
	 *
	 * @param  string  $field
	 * @param  \Symfony\Component\HttpFoundation\Request  $request 
	 * @return \Symfony\Component\HttpFoundation\Response|null
	 */
	public function basic($field = 'email', Request $request = null)
	{
		if ($this->check()) return;

		$request = $request ?: $this->getRequest();

		// If a username is set on the HTTP basic request, we will return out without
		// interrupting the request lifecycle. Otherwise, we'll need to generate a
		// request indicating that the given credentials were invalid for login.
		if ($this->attemptBasic($request, $field)) return;

		return $this->getBasicResponse();
	}

	/**
	 * Perform a stateless HTTP Basic login attempt.
	 *
	 * @param  string  $field
	 * @param  \Symfony\Component\HttpFoundation\Request  $request 
	 * @return \Symfony\Component\HttpFoundation\Response|null
	 */
	public function onceBasic($field = 'email', Request $request = null)
	{
		$request = $request ?: $this->getRequest();

		if ( ! $this->once($this->getBasicCredentials($request, $field)))
		{
			return $this->getBasicResponse();
		}
	}

	/**
	 * Attempt to authenticate using basic authentication.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request 
	 * @param  string  $field
	 * @return bool
	 */
	protected function attemptBasic(Request $request, $field)
	{
		if ( ! $request->getUser()) return false;

		return $this->attempt($this->getBasicCredentials($request, $field));
	}

	/**
	 * Get the credential array for a HTTP Basic request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request 
	 * @param  string  $field
	 * @return array
	 */
	protected function getBasicCredentials(Request $request, $field)
	{
		return array($field => $request->getUser(), 'password' => $request->getPassword());
	}

	/**
	 * Get the response for basic authentication.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function getBasicResponse()
	{
		$headers = array('WWW-Authenticate' => 'Basic');

		return new Response('Invalid credentials.', 401, $headers);
	}

	/**
	 * Attempt to authenticate a user using the given credentials.
	 *
	 * @param  array  $credentials
	 * @param  bool   $remember
	 * @param  bool   $login
	 * @return bool
	 */
	public function attempt(array $credentials = array(), $remember = false, $login = true)
	{
		$this->fireAttemptEvent($credentials, $remember, $login);

		$user = $this->provider->retrieveByCredentials($credentials);

		// If an implementation of UserInterface was returned, we'll ask the provider
		// to validate the user against the given credentials, and if they are in
		// fact valid we'll log the users into the application and return true.
		if ($user instanceof UserInterface)
		{
			if ($this->provider->validateCredentials($user, $credentials))
			{
				if ($login) $this->login($user, $remember);

				return true;
			}
		}

		return false;
	}

	/**
	 * Fire the attempt event with the arguments.
	 *
	 * @param  array  $credentials
	 * @param  bool   $remember
	 * @param  bool   $login
	 * @return void
	 */
	protected function fireAttemptEvent(array $credentials, $remember, $login)
	{
		if ($this->events)
		{
			$payload = array($credentials, $remember, $login);

			$this->events->fire('auth.attempt', $payload);
		}
	}

	/**
	 * Register an authentication attempt event listener.
	 *
	 * @param  mixed  $callback
	 * @return void
	 */
	public function attempting($callback)
	{
		if ($this->events)
		{
			$this->events->listen('auth.attempt', $callback);
		}
	}

	/**
	 * Log a user into the application.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  bool  $remember
	 * @return void
	 */
	public function login(UserInterface $user, $remember = false)
	{
		$id = $user->getAuthIdentifier();

		$this->session->put($this->getName(), $id);

		// If the user should be permanently "remembered" by the application we will
		// queue a permanent cookie that contains the encrypted copy of the user
		// identifier. We will then decrypt this later to retrieve the users.
		if ($remember)
		{
			$this->queueRecallerCookie($id);
		}

		// If we have an event dispatcher instance set we will fire an event so that
		// any listeners will hook into the authentication events and run actions
		// based on the login and logout events fired from the guard instances.
		if (isset($this->events))
		{
			$this->events->fire('auth.login', array($user, $remember));
		}

		$this->setUser($user);
	}

	/**
	 * Log the given user ID into the application.
	 *
	 * @param  mixed  $id
	 * @param  bool   $remember
	 * @return \Illuminate\Auth\UserInterface
	 */
	public function loginUsingId($id, $remember = false)
	{
		$this->session->put($this->getName(), $id);

		return $this->login($this->provider->retrieveById($id), $remember);
	}

	/**
	 * Log the given user ID into the application without sessions or cookies.
	 *
	 * @param  mixed  $id
	 * @return bool
	 */
	public function onceUsingId($id)
	{
		$this->setUser($this->provider->retrieveById($id));

		return $this->user instanceof UserInterface;
	}

	/**
	 * Queue the recaller cookie into the cookie jar.
	 *
	 * @param  string  $id
	 * @return void
	 */
	protected function queueRecallerCookie($id)
	{
		$this->getCookieJar()->queue($this->createRecaller($id));
	}

	/**
	 * Create a remember me cookie for a given ID.
	 *
	 * @param  mixed  $id
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	protected function createRecaller($id)
	{
		return $this->getCookieJar()->forever($this->getRecallerName(), $id);
	}

	/**
	 * Log the user out of the application.
	 *
	 * @return void
	 */
	public function logout()
	{
		$user = $this->user();

		// If we have an event dispatcher instance, we can fire off the logout event
		// so any further processing can be done. This allows the developer to be
		// listening for anytime a user signs out of this application manually.
		$this->clearUserDataFromStorage();

		if (isset($this->events))
		{
			$this->events->fire('auth.logout', array($user));
		}

		// Once we have fired the logout event we will clear the users out of memory
		// so they are no longer available as the user is no longer considered as
		// being signed into this application and should not be available here.
		$this->user = null;

		$this->loggedOut = true;
	}

	/**
	 * Remove the user data from the session and cookies.
	 *
	 * @return void
	 */
	protected function clearUserDataFromStorage()
	{
		$this->session->forget($this->getName());

		$recaller = $this->getRecallerName();

		$this->getCookieJar()->queue($this->getCookieJar()->forget($recaller));
	}

	/**
	 * Get the cookie creator instance used by the guard.
	 *
	 * @return \Illuminate\Cookie\CookieJar
	 */
	public function getCookieJar()
	{
		if ( ! isset($this->cookie))
		{
			throw new \RuntimeException("Cookie jar has not been set.");
		}

		return $this->cookie;
	}

	/**
	 * Set the cookie creator instance used by the guard.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookie
	 * @return void
	 */
	public function setCookieJar(CookieJar $cookie)
	{
		$this->cookie = $cookie;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return \Illuminate\Events\Dispatcher
	 */
	public function getDispatcher()
	{
		return $this->events;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \Illuminate\Events\Dispatcher
	 */
	public function setDispatcher(Dispatcher $events)
	{
		$this->events = $events;
	}

	/**
	 * Get the session store used by the guard.
	 *
	 * @return \Illuminate\Session\Store
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * Get the user provider used by the guard.
	 *
	 * @return \Illuminate\Auth\UserProviderInterface
	 */
	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * Set the user provider used by the guard.
	 *
	 * @param  \Illuminate\Auth\UserProviderInterface  $provider
	 * @return void
	 */
	public function setProvider(UserProviderInterface $provider)
	{
		$this->provider = $provider;
	}

	/**
	 * Return the currently cached user of the application.
	 *
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Set the current user of the application.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @return void
	 */
	public function setUser(UserInterface $user)
	{
		$this->user = $user;

		$this->loggedOut = false;
	}

	/**
	 * Get the current request instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request ?: Request::createFromGlobals();
	}

	/**
	 * Set the current request instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request
	 * @return \Illuminate\Auth\Guard
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Get a unique identifier for the auth session value.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'login_'.md5(get_class($this));
	}

	/**
	 * Get the name of the cookie used to store the "recaller".
	 *
	 * @return string
	 */
	public function getRecallerName()
	{
		return 'remember_'.md5(get_class($this));
	}

}
