<?php namespace Illuminate\Validation;

use Exception;
use JsonSerializable;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\MessageProviderInterface;

class ValidationException extends Exception implements ArrayableInterface, JsonableInterface, JsonSerializable, MessageProviderInterface {

	/**
	 * The validator data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The validator rules.
	 *
	 * @var array
	 */
	protected $rules;

	/**
	 * The message bag instance.
	 *
	 * @var \Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	 * Construct a new validation exception instance.
	 *
	 * @param \Illuminate\Validation\Validator  $validator
	 * @param string  $message
	 */
	public function __construct(Validator $validator, $message = null)
	{
		$this->data = $validator->getData();
		$this->rules = $validator->getRules();
		$this->errors = clone $validator->getMessageBag();

		parent::__construct($message ?: 'Validation failed');
	}

	/**
	 * Get the array of data the validation was done with.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Get the array of rules that the validation was done with.
	 *
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * Get a flat array of all error messages.
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors->all();
	}

	/**
	 * Get the message bag instance.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getMessageBag()
	{
		return $this->errors;
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return ['errors' => $this->getErrors()];
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->jsonSerialize(), $options);
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getErrors();
	}

	/**
	 * Convert the exception to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$errors = implode(PHP_EOL, $this->getErrors());

		return $this->getMessage() . PHP_EOL . $errors;
	}

}
