<?php namespace Illuminate\Contracts\Auth;

interface Remindable {

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail();

}
