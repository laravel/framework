<?php namespace Illuminate\Contracts\Validation;

interface Factory {

	/**
	 * Create a new Validator instance.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @param  array  $messages
	 * @param  array  $customAttributes
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	public function make(array $data, array $rules, array $messages = array(), array $customAttributes = array());

}
