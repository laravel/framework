<?php namespace Illuminate\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Contracts\JsonableInterface;

class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse {

	use ResponseTrait;

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

}