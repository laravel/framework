<?php namespace Illuminate\Hashing;

class BcryptHasher implements HasherInterface {

	/**
	 * Create a new Bcrypt hasher instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		if (version_compare(PHP_VERSION, '5.3.7') < 0)
		{
			throw new \RuntimeException("Bcrypt hashing requires PHP 5.3.7");
		}
	}

	/**
	 * Hash the given value.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function make($value, array $options = array())
	{
		$cost = isset($options['rounds']) ? $options['rounds'] : 8;

		return password_hash($value, PASSWORD_BCRYPT, array('cost' => $cost));
	}

	/**
	 * Check the given plain value against a hash.
	 *
	 * @param  string  $value
	 * @param  string  $hashedValue
	 * @param  array   $options
	 * @return bool
	 */
	public function check($value, $hashedValue, array $options = array())
	{
		return password_verify($value, $hashedValue);
	}

}