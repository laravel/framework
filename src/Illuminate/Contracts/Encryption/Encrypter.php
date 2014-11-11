<?php namespace Illuminate\Contracts\Encryption;

interface Encrypter {

	/**
	 * Encrypt the given value.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function encrypt($value);

	/**
	 * Decrypt the given value.
	 *
	 * @param  string  $payload
	 * @return string
	 */
	public function decrypt($payload);

	/**
	 * Set the encryption mode.
	 *
	 * @param  string  $mode
	 * @return void
	 */
	public function setMode($mode);

	/**
	 * Set the encryption cipher.
	 *
	 * @param  string  $cipher
	 * @return void
	 */
	public function setCipher($cipher);

}
