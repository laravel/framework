<?php namespace Illuminate\Http;

use ArrayObject;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\RenderableInterface;

class Response extends \Symfony\Component\HttpFoundation\Response {

	/**
	 * The request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public $original;

	/**
	 * Set a header on the Response.
	 *
	 * @param  string  $key
	 * @param  string  $value
	 * @param  bool    $replace
	 * @return \Illuminate\Http\Response
	 */
	public function header($key, $value, $replace = true)
	{
		$this->headers->set($key, $value, $replace);

		return $this;
	}

	/**
	 * Add a cookie to the response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Cookie  $cookie
	 * @return \Illuminate\Http\Response
	 */
	public function withCookie(Cookie $cookie)
	{
		$this->headers->setCookie($cookie);

		return $this;
	}

	/**
	 * Set the content on the response.
	 *
	 * @param  mixed  $content
	 * @return void
	 */
	public function setContent($content)
	{
		$this->original = $content;

		// If the content is "JSONable" we will set the appropriate header and convert
		// the content to JSON. This is useful when returning something like models
		// from routes that will be automatically transformed to their JSON form.
		if ($this->shouldBeJson($content))
		{
			$this->headers->set('Content-Type', 'application/json');

			$content = $this->morphToJson($content);
		}

		// If this content implements the "RenderableInterface", then we will call the
		// render method on the object so we will avoid any "__toString" exceptions
		// that might be thrown and have their errors obscured by PHP's handling.
		elseif ($content instanceof RenderableInterface)
		{
			$content = $content->render();
		}

		return parent::setContent($content);
	}

	/**
	 * Morph the given content into JSON.
	 *
	 * @param  mixed   $content
	 * @return string
	 */
	protected function morphToJson($content)
	{
		if ($content instanceof JsonableInterface) return $content->toJson();

		return json_encode($content);
	}

	/**
	 * Determine if the given content should be turned into JSON.
	 *
	 * @param  mixed  $content
	 * @return bool
	 */
	protected function shouldBeJson($content)
	{
		return $content instanceof JsonableInterface ||
			   $content instanceof ArrayObject ||
			   is_array($content);
	}

	/**
	 * Get the original response content.
	 *
	 * @return mixed
	 */
	public function getOriginalContent()
	{
		return $this->original;
	}

	/**
	 * Remember a response.
	 *
	 * If $etag is set to true, an etag will be automatically generated.
	 *
	 * @param string|bool|null      $etag
	 * @param \Datetime|Carbon|null $lastModified
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function remember($etag = true, $lastModified = null)
	{
		if ($etag === true)
		{
			$etag = sha1(json_encode($this->content));
		}

		$this->setEtag($etag)->setLastModified($lastModified);

		$this->isNotModified($this->getRequest());

		return $this;
	}

	/**
	 * Get the request instance.
	 *
	 * @return \Illuminate\Http\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the request instance.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

}
