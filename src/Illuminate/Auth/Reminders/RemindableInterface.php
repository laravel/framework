<?php namespace Illuminate\Auth\Reminders;

interface RemindableInterface {

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail();
    
	/**
	 * Get the id of the user
	 *
	 * @return int
	 */
    public function getAuthIdentifier();

}