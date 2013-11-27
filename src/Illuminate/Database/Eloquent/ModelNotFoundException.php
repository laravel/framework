<?php namespace Illuminate\Database\Eloquent;

class ModelNotFoundException extends \RuntimeException {
	/**
	 * Name of the affected eloquent model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Set the affected eloquent model.
	 *
	 * @param  string   $model
	 * @return ModelNotFoundException
	 */
	public function setModel($model)
	{
		$this->model = $model;
		$this->message = "{$model} not found";

		return $this;
	}

	/**
	 * Get the affected eloquent model.
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

}
