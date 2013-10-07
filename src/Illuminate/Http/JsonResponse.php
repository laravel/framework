<?php namespace Illuminate\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Contracts\JsonableInterface;

class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse {

	/**
	 * Get the json_decoded data from the response
	 *
	 * @param  bool $assoc
	 * @param  int  $depth
	 * @param  int  $options
	 * @return mixed
	 */
	public function getData($assoc = false, $depth = 512, $options = 0)
	{
		return json_decode($this->data, $assoc, $depth, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setData($data = array())
	{
		$this->data = $data instanceof JsonableInterface ? $data->toJson() : json_encode($data);

		return $this->update();
	}

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

}