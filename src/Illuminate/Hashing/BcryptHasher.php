<?php namespace Illuminate\Hashing;

class BcryptHasher implements HasherInterface {

	/**
	 * Hash the given value.
	 *
	 * @param  string  $value
	 * @return array   $options
	 * @return string
	 */
	public function make($value, array $options = array())
	{
		$rounds = isset($options['rounds']) ? $options['rounds'] : 8;

		$work = str_pad($rounds, 2, '0', STR_PAD_LEFT);

		// Bcrypt expects the salt to be 22 base64 encoded characters including dots
		// and slashes. We will get rid of the plus signs included in the base64
		// data and replace them all with dots so it's appropriately encoded.
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$salt = openssl_random_pseudo_bytes(16);
		}
		else
		{
			$salt = $this->getRandomSalt();
		}

		$salt = substr(strtr(base64_encode($salt), '+', '.'), 0 , 22);

		return crypt($value, '$2a$'.$work.'$'.$salt);
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
		return crypt($value, $hashedValue) === $hashedValue;
	}

	/**
	 * Get a random salt to use during hashing.
	 *
	 * @return string
	 */
	protected function getRandomSalt()
	{
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr(str_shuffle(str_repeat($pool, 5)), 0, 40);
	}

}