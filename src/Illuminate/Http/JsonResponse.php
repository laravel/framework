<?php namespace Illuminate\Http;

class JsonResponse extends \Symfony\Component\HttpFoundation\JsonResponse {

	/**
	 * {@inheritdoc}
	 */
	public function setData($data = array())
	{
		$this->data = json_encode($data);

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

}
