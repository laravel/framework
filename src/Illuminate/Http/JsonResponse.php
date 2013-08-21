<?php namespace Illuminate\Http;

/*
 * With the exception of the getData and the setData methods, all this code
 * comes from the \Symfony\Component\HttpFoundation\JsonResponse class
 */

use Illuminate\Support\Contracts\JsonableInterface;

class JsonResponse extends Response {
	protected $data;
	protected $callback;

	/**
	 * Constructor.
	 *
	 * @param mixed   $data    The response data
	 * @param integer $status  The response status code
	 * @param array   $headers An array of response headers
	 */
	public function __construct($data = null, $status = 200, $headers = array())
	{
		parent::__construct('', $status, $headers);

		if (null === $data) {
			$data = new \ArrayObject();
		}
		$this->setData($data);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function create($data = null, $status = 200, $headers = array())
	{
		return new static($data, $status, $headers);
	}

	/**
	 * Sets the JSONP callback.
	 *
	 * @param string $callback
	 *
	 * @return JsonResponse
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setCallback($callback = null)
	{
		if (null !== $callback) {
			// taken from http://www.geekality.net/2011/08/03/valid-javascript-identifier/
			$pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
			$parts = explode('.', $callback);
			foreach ($parts as $part) {
				if (!preg_match($pattern, $part)) {
					throw new \InvalidArgumentException('The callback name is not valid.');
				}
			}
		}

		$this->callback = $callback;

		return $this->update();
	}

	/**
	 * Sets the data to be sent as json.
	 *
	 * @param mixed $data
	 *
	 * @return JsonResponse
	 */
	public function setData($data = array())
	{
		$this->data = $data instanceof JsonableInterface ? $data->toJson() : json_encode($data);

		return $this->update();
	}

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
	 * Updates the content and headers according to the json data and callback.
	 *
	 * @return JsonResponse
	 */
	protected function update()
	{
		if (null !== $this->callback) {
			// Not using application/javascript for compatibility reasons with older browsers.
			$this->headers->set('Content-Type', 'text/javascript');

			return $this->setContent(sprintf('%s(%s);', $this->callback, $this->data));
		}

		// Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
		// in order to not overwrite a custom definition.
		if (!$this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
			$this->headers->set('Content-Type', 'application/json');
		}

		return $this->setContent($this->data);
	}

}
