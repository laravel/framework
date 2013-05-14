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

}