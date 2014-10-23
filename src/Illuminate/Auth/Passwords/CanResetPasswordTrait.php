<?php namespace Illuminate\Auth\Passwords;

trait CanResetPasswordTrait {

	/**
	 * Get the e-mail address where password reset links are sent.
	 *
	 * @return string
	 */
	public function getEmailForPasswordReset()
	{
		return $this->email;
	}

}
