<?php namespace Illuminate\Auth\Reminders;

interface ReminderRepositoryInterface {

	/**
	 * Create a new reminder record and token.
	 *
	 * @param  \Illuminate\Auth\RemindableInterface  $user
	 * @return string
	 */
	public function create(RemindableInterface $user);

	/**
	 * Determine if a reminder record exists and is valid.
	 *
	 * @param  \Illuminate\Auth\RemindableInterface  $user
	 * @param  string  $token
	 * @return bool
	 */
	public function exists(RemindableInterface $user, $token);

	/**
	 * Delete a reminder record by token.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function delete($token);

}