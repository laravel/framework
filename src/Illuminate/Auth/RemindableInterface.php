<?php namespace Illuminate\Auth;

interface RemindableInterface {

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail();

}