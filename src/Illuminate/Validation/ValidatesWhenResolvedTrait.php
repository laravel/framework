<?php namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Contracts\Validation\UnauthorizedException;

/**
 * Provides default implementation of ValidatesWhenResolved contract.
 */
trait ValidatesWhenResolvedTrait {

	/**
	 * Validate the class instance.
	 *
	 * @return void
	 */
	public function validate()
	{
		$instance = $this->getValidatorInstance();

		if ( ! $this->passesAuthorization())
		{
			$this->failedAuthorization();
		}
		elseif ( ! $instance->passes())
		{
			$this->failedValidation($instance);
		}
	}

	/**
	 * Get the validator instance for the request.
	 *
	 * @return \Illuminate\Validation\Validator
	 */
	protected function getValidatorInstance()
	{
		return $this->validator();
	}

	/**
	 * Handle a failed validation attempt.
	 *
	 * @param  \Illuminate\Validation\Validator  $validator
	 * @return mixed
	 */
	protected function failedValidation(Validator $validator)
	{
		throw new ValidationException($validator);
	}

	/**
	 * Deteremine if the request passes the authorization check.
	 *
	 * @return bool
	 */
	protected function passesAuthorization()
	{
		if (method_exists($this, 'authorize'))
		{
			return $this->authorize();
		}

		return true;
	}

	/**
	 * Handle a failed authorization attempt.
	 *
	 * @return mixed
	 */
	protected function failedAuthorization()
	{
		throw new UnauthorizedException;
	}

}
