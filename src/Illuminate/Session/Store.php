<?php namespace Illuminate\Session;

use Closure;
use ArrayAccess;
use Illuminate\Support\Str;
use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Contracts\SessionStoreInterface;

abstract class Store implements ArrayAccess {

	/**
	 * The current session payload.
	 *
	 * @var array
	 */
	protected $session;

	/**
	 * Indicates if the session already existed.
	 *
	 * @var bool
	 */
	protected $exists = true;

	/**
	 * The session lifetime in minutes.
	 *
	 * @var int
	 */
	protected $lifetime = 120;

	/**
	 * The chances of hitting the sweeper lottery.
	 *
	 * @var array
	 */
	protected $sweep = array(2, 100);

	/**
	 * The session cookie options array.
	 *
	 * @var array
	 */
	protected $cookie = array(
		'name'      => 'illuminate_session',
		'path'      => '/',
		'domain'    => null,
		'secure'    => false,
		'http_only' => true,
	);

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @return array|null
	 */
	abstract public function retrieveSession($id);

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	abstract public function createSession($id, array $session, Response $response);

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	abstract public function updateSession($id, array $session, Response $response);

	/**
	 * Load the session for the request.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookies
	 * @param  string  $name
	 * @return void
	 */
	public function start(CookieJar $cookies, $name)
	{
		$id = $cookies->get($name);

		// If the session ID was available via the request cookies we'll just retrieve
		// the session payload from the driver and check the given session to make
		// sure it's valid. All the data fetching is implemented by the driver.
		if ( ! is_null($id))
		{
			$session = $this->retrieveSession($id);
		}

		// If the session is not valid, we will create a new payload and will indicate
		// that the session has not yet been created. These freshly created session
		// payloads will be given a fresh session ID so there are not collisions.
		if ( ! isset($session) or $this->isInvalid($session))
		{
			$this->exists = false;

			$session = $this->createFreshSession();
		}

		// Once the session payloads have been created or loaded we will set it to an
		// internal values that are managed by the driver. The values are not sent
		// back into storage until the sessions are closed after these requests.
		$this->session = $session;
	}

	/**
	 * Create a fresh session payload.
	 *
	 * @return array
	 */
	protected function createFreshSession()
	{
		$flash = $this->createData();

		return array('id' => $this->createSessionID(), 'data' => $flash);
	}

	/**
	 * Create a new, empty session data array.
	 *
	 * @return array
	 */
	protected function createData()
	{
		$token = $this->createSessionID();

		return array('_token' => $token, ':old:' => array(), ':new:' => array());
	}

	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	protected function createSessionID()
	{
		return Str::random(40);
	}

	/**
	 * Determine if the given session is invalid.
	 *
	 * @param  array  $session
	 * @return bool
	 */
	protected function isInvalid($session)
	{
		if ( ! is_array($session)) return true;

		return $this->isExpired($session);
	}

	/**
	 * Determine if the given session is expired.
	 *
	 * @param  array  $session
	 * @return bool
	 */
	protected function isExpired($session)
	{
		if ($this->lifetime == 0) return false;

		return (time() - $session['last_activity']) > ($this->lifetime * 60);
	}

	/**
	 * Get the full array of session data, including flash data.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->session['data'];
	}

	/**
	 * Determine if the session contains a given item.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	/**
	 * Get the requested item from the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$me = $this;

		// First we will check for the value in the general session data and if it
		// is not present in that array we'll check the session flash datas to
		// get the data from there. If neither is there we give the default.
		$data = $this->session['data'];

		return array_get($data, $key, function() use ($me, $key, $default)
		{
			return $me->getFlash($key, $default);
		});
	}

	/**
	 * Get the request item from the flash data.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function getFlash($key, $default = null)
	{
		$data = $this->session['data'];

		// Session flash data is only persisted for the next request into the app
		// which makes it convenient for temporary status messages or various
		// other strings. We'll check all of this flash data for the items.
		if ($value = array_get($data, ":new:.$key"))
		{
			return $value;
		}

		// The "old" flash data are the data flashed during the previous request
		// while the "new" data is the data flashed during the course of this
		// current request. Usually developers will be retrieving the olds.
		if ($value = array_get($data, ":old:.$key"))
		{
			return $value;
		}

		return value($default);
	}

	/**
	 * Determine if the old input contains an item.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasOldInput($key)
	{
		return ! is_null($this->getOldInput($key));
	}

	/**
	 * Get the requested item from the flashed input array.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function getOldInput($key = null, $default = null)
	{
		$input = $this->get('__old_input', array());

		// Input that is flashed to the session can be easily retrieved by the
		// developer, making repopulating old forms and the like much more
		// convenient, since the request's previous input is available.
		if (is_null($key)) return $input;

		return array_get($input, $key, $default);
	}

	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 */
	public function getToken()
	{
		return $this->get('_token');
	}

	/**
	 * Put a key / value pair in the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function put($key, $value)
	{
		array_set($this->session['data'], $key, $value);
	}

	/**
	 * Flash a key / value pair to the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function flash($key, $value)
	{
		array_set($this->session['data'][':new:'], $key, $value);
	}

	/**
	 * Flash an input array to the session.
	 *
	 * @param  array  $value
	 * @return void
	 */
	public function flashInput(array $value)
	{
		return $this->flash('__old_input', $value);
	}

	/**
	 * Keep all of the session flash data from expiring.
	 *
	 * @return void
	 */
	public function reflash()
	{
		$old = $this->session['data'][':old:'];

		$new = $this->session['data'][':new:'];

		$this->session['data'][':new:'] = array_merge($new, $old);
	}

	/**
	 * Keep a session flash item from expiring.
	 *
	 * @param  string|array  $keys
	 * @return void
	 */
	public function keep($keys)
	{
		foreach ((array) $keys as $key)
		{
			$this->flash($key, $this->get($key));
		}
	}

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		array_forget($this->session['data'], $key);
	}

	/**
	 * Remove all of the items from the session.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->session['data'] = $this->createData();
	}

	/**
	 * Generate a new session identifier.
	 *
	 * @return string
	 */
	public function regenerate()
	{
		$this->exists = false;

		return $this->session['id'] = $this->createSessionID();
	}

	/**
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @param  int  $lifetime
	 * @return void
	 */
	public function finish(Response $response, $lifetime)
	{
		$time = $this->getCurrentTime();

		// First we will set the last activity timestamp on the session and age the
		// session flash data so the old data is gone when subsequent calls into
		// the application are made. Then we'll call the driver store methods.
		$this->session['last_activity'] = $time;

		$id = $this->getSessionID();

		$this->ageFlashData();

		// We'll distinguish between updating and creating sessions since it might
		// matter to the driver. Most drivers will probably be able to use the
		// same code regardless of whether the session is new or not though.
		if ($this->exists)
		{
			$this->updateSession($id, $this->session, $response);
		}
		else
		{
			$this->createSession($id, $this->session, $response);
		}

		// If this driver implements the "Sweeper" interface and hits the sweepers
		// lottery we will sweep sessions from storage that are expired so the
		// storage spot does not get junked up with expired session storage.
		if ($this instanceof Sweeper and $this->hitsLottery())
		{
			$this->sweep($time - ($this->lifetime * 60));
		}
	}

	/**
	 * Age the session flash data.
	 *
	 * @return void
	 */
	protected function ageFlashData()
	{
		$this->session['data'][':old:'] = $this->session['data'][':new:'];

		$this->session['data'][':new:'] = array();
	}

	/**
	 * Get the current timestamp.
	 *
	 * @return int
	 */
	protected function getCurrentTime()
	{
		return time();
	}

	/**
	 * Determine if the request hits the sweeper lottery.
	 *
	 * @return bool
	 */
	public function hitsLottery()
	{
		return mt_rand(1, $this->sweep[1]) <= $this->sweep[0];
	}

	/**
	 * Write the session cookie to the response.
	 *
	 * @param  \Illuminate\Cookie\CookieJar  $cookie
	 * @param  string  $name
	 * @param  int     $lifetime
	 * @param  string  $path
	 * @param  string  $domain
	 * @return void
	 */
	public function getCookie(CookieJar $cookie, $name, $lifetime, $path, $domain)
	{
		return $cookie->make($name, $this->getSessionId(), $lifetime, $path, $domain);
	}

	/**
	 * Get the session payload.
	 *
	 * @var array
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * Set the entire session payload.
	 *
	 * @param  array  $session
	 * @return void
	 */
	public function setSession($session)
	{
		$this->session = $session;
	}

	/**
	 * Get the current session ID.
	 *
	 * @return string
	 */
	public function getSessionID()
	{
		if (isset($this->session['id'])) return $this->session['id'];
	}

	/**
	 * Get the session's last activity UNIX timestamp.
	 *
	 * @return int
	 */
	public function getLastActivity()
	{
		if (isset($this->session['last_activity']))
		{
			return $this->session['last_activity'];
		}
	}

	/**
	 * Determine if the session exists in storage.
	 *
	 * @return bool
	 */
	public function sessionExists()
	{
		return $this->exists;
	}

	/**
	 * Set the existence of the session.
	 *
	 * @param  bool  $value
	 * @return void
	 */
	public function setExists($value)
	{
		$this->exists = $value;
	}

	/**
	 * Set the session cookie name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setCookieName($name)
	{
		return $this->setCookieOption('name', $name);
	}

	/**
	 * Get the given cookie option.
	 *
	 * @param  string  $option
	 * @return mixed
	 */
	public function getCookieOption($option)
	{
		return $this->cookie[$option];
	}

	/**
	 * Set the given cookie option.
	 *
	 * @param  string  $option
	 * @param  mixed   $value
	 * @return void
	 */
	public function setCookieOption($option, $value)
	{
		$this->cookie[$option] = $value;
	}

	/**
	 * Set the session lifetime.
	 *
	 * @param  int   $minutes
	 * @return void
	 */
	public function setLifetime($minutes)
	{
		$this->lifetime = $minutes;
	}

	/**
	 * Set the chances of hitting the Sweeper lottery.
	 *
	 * @param  array  $values
	 * @return void
	 */
	public function setSweepLottery(array $values)
	{
		$this->sweep = $values;
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Store a value at the given offset.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		return $this->put($key, $value);
	}

	/**
	 * Remove the value at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->forget($key);
	}

}
