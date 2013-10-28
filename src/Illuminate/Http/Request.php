<?php namespace Illuminate\Http;

use Illuminate\Session\Store as SessionStore;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest {

	/**
	 * The decoded JSON content for the request.
	 *
	 * @var string
	 */
	protected $json;

	/**
	 * The Illuminate session store implementation.
	 *
	 * @var \Illuminate\Session\Store
	 */
	protected $sessionStore;

	/**
	 * Return the Request instance.
	 *
	 * @return \Illuminate\Http\Request
	 */
	public function instance()
	{
		return $this;
	}

	/**
	 * Get the root URL for the application.
	 *
	 * @return string
	 */
	public function root()
	{
		return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
	}

	/**
	 * Get the URL (no query string) for the request.
	 *
	 * @return string
	 */
	public function url()
	{
		return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
	}	

	/**
	 * Get the full URL for the request.
	 *
	 * @return string
	 */
	public function fullUrl()
	{
		$query = $this->getQueryString();

		return $query ? $this->url().'?'.$query : $this->url();
	}

	/**
	 * Get the current path info for the request.
	 *
	 * @return string
	 */
	public function path()
	{
		$pattern = trim($this->getPathInfo(), '/');

		return $pattern == '' ? '/' : $pattern;
	}

	/**
	 * Get a segment from the URI (1 based index).
	 *
	 * @param  string  $index
	 * @param  mixed   $default
	 * @return string
	 */
	public function segment($index, $default = null)
	{
		$segments = explode('/', trim($this->getPathInfo(), '/'));

		$segments = array_filter($segments, function($v) { return $v != ''; });

		return array_get($segments, $index - 1, $default);
	}

	/**
	 * Get all of the segments for the request path.
	 *
	 * @return array
	 */
	public function segments()
	{
		$path = $this->path();

		return $path == '/' ? array() : explode('/', $path);
	}

	/**
	 * Determine if the current request URI matches a pattern.
	 *
	 * @param  string  $pattern
	 * @return bool
	 */
	public function is($pattern)
	{
		foreach (func_get_args() as $pattern)
		{
			if (str_is($pattern, $this->path()))
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Determine if the request is the result of an AJAX call.
	 * 
	 * @return bool
	 */
	public function ajax()
	{
		return $this->isXmlHttpRequest();
	}

	/**
	 * Determine if the request is over HTTPS.
	 *
	 * @return bool
	 */
	public function secure()
	{
		return $this->isSecure();
	}

	/**
	 * Determine if the request contains a given input item.
	 *
	 * @param  string|array  $key
	 * @return bool
	 */
	public function has($key)
	{
		if (count(func_get_args()) > 1)
		{
			foreach (func_get_args() as $value)
			{
				if ( ! $this->has($value)) return false;
			}

			return true;
		}

		if (is_bool($this->input($key)) or is_array($this->input($key)))
		{
			return true;
		}

		return trim((string) $this->input($key)) !== '';
	}

	/**
	 * Get all of the input and files for the request.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->input() + $this->files->all();
	}

	/**
	 * Retrieve an input item from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function input($key = null, $default = null)
	{
		$input = $this->getInputSource()->all() + $this->query->all();

		return array_get($input, $key, $default);
	}

	/**
	 * Get a subset of the items from the input data.
	 *
	 * @param  array  $keys
	 * @return array
	 */
	public function only($keys)
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		return array_only($this->input(), $keys) + array_fill_keys($keys, null);
	}

	/**
	 * Get all of the input except for a specified array of items.
	 *
	 * @param  array  $keys
	 * @return array
	 */
	public function except($keys)
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		$results = $this->input();

		foreach ($keys as $key) array_forget($results, $key);

		return $results;
	}

	/**
	 * Retrieve a query string item from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function query($key = null, $default = null)
	{
		return $this->retrieveItem('query', $key, $default);
	}

	/**
	 * Retrieve a cookie from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function cookie($key = null, $default = null)
	{
		return $this->retrieveItem('cookies', $key, $default);
	}

	/**
	 * Retrieve a file from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return \Symfony\Component\HttpFoundation\File\UploadedFile|array
	 */
	public function file($key = null, $default = null)
	{
		return array_get($this->files->all(), $key, $default);
	}

	/**
	 * Determine if the uploaded data contains a file.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasFile($key)
	{
		return $this->file($key) instanceof \SplFileInfo;
	}

	/**
	 * Retrieve a header from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function header($key = null, $default = null)
	{
		return $this->retrieveItem('headers', $key, $default);
	}

	/**
	 * Retrieve a server variable from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function server($key = null, $default = null)
	{
		return $this->retrieveItem('server', $key, $default);
	}

	/**
	 * Retrieve an old input item.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function old($key = null, $default = null)
	{
		return $this->getSessionStore()->getOldInput($key, $default);
	}

	/**
	 * Flash the input for the current request to the session.
	 *
	 * @param  string $filter
	 * @param  array  $keys
	 * @return void
	 */
	public function flash($filter = null, $keys = array())
	{
		$flash = ( ! is_null($filter)) ? $this->$filter($keys) : $this->input();

		$this->getSessionStore()->flashInput($flash);
	}

	/**
	 * Flash only some of the input to the session.
	 *
	 * @param  dynamic  string
	 * @return void
	 */
	public function flashOnly($keys)
	{
		$keys = is_array($keys) ? $keys : func_get_args();
		
		return $this->flash('only', $keys);
	}

	/**
	 * Flash only some of the input to the session.
	 *
	 * @param  dynamic  string
	 * @return void
	 */
	public function flashExcept($keys)
	{
		$keys = is_array($keys) ? $keys : func_get_args();
		
		return $this->flash('except', $keys);
	}

	/**
	 * Flush all of the old input from the session.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->getSessionStore()->flashInput(array());
	}

	/**
	 * Retrieve a parameter item from a given source.
	 *
	 * @param  string  $source
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	protected function retrieveItem($source, $key, $default)
	{
		if (is_null($key))
		{
			return $this->$source->all();
		}
		else
		{
			return $this->$source->get($key, $default, true);
		}
	}

	/**
	 * Merge new input into the current request's input array.
	 *
	 * @param  array  $input
	 * @return void
	 */
	public function merge(array $input)
	{
		$this->getInputSource()->add($input);
	}

	/**
	 * Replace the input for the current request.
	 *
	 * @param  array  $input
	 * @return void
	 */
	public function replace(array $input)
	{
		$this->getInputSource()->replace($input);
	}

	/**
	 * Get the JSON payload for the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function json($key = null, $default = null)
	{
		if ( ! isset($this->json))
		{
			$this->json = new ParameterBag((array) json_decode($this->getContent(), true));
		}

		if (is_null($key)) return $this->json;

		return array_get($this->json->all(), $key, $default);
	}

	/**
	 * Get the input source for the request.
	 *
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected function getInputSource()
	{
		if ($this->isJson()) return $this->json();

		return $this->getMethod() == 'GET' ? $this->query : $this->request;
	}

	/**
	 * Determine if the request is sending JSON.
	 *
	 * @return bool
	 */
	public function isJson()
	{
		return str_contains($this->header('CONTENT_TYPE'), '/json');
	}

	/**
	 * Determine if the current request is asking for JSON in return.
	 *
	 * @return bool
	 */
	public function wantsJson()
	{
		$acceptable = $this->getAcceptableContentTypes();

		return isset($acceptable[0]) and $acceptable[0] == 'application/json';
	}

	/**
	 * Get the data format expected in the response.
	 *
	 * @return string
	 */
	public function format($default = 'html')
	{
		foreach ($this->getAcceptableContentTypes() as $type)
		{
			if ($format = $this->getFormat($type)) return $format;
		}

		return $default;
	}

	/**
	 * Get the Illuminate session store implementation.
	 *
	 * @return \Illuminate\Session\Store
	 */
	public function getSessionStore()
	{
		if ( ! isset($this->sessionStore))
		{
			throw new \RuntimeException("Session store not set on request.");
		}

		return $this->sessionStore;
	}

	/**
	 * Set the Illuminate session store implementation.
	 *
	 * @param  \Illuminate\Session\Store  $session
	 * @return void
	 */
	public function setSessionStore(SessionStore $session)
	{
		$this->sessionStore = $session;
	}

	/**
	 * Determine if the session store has been set.
	 *
	 * @return bool
	 */
	public function hasSessionStore()
	{
		return isset($this->sessionStore);
	}

}
