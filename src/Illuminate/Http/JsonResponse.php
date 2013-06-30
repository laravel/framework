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
	 * @return \StdClass
	 */
	public function getData()
	{
		return json_decode($this->data);
	}

}
