<?php namespace Illuminate\Auth\Reminders;

interface ReminderRepositoryInterface {

	/**
	 * Create a new reminder record and token.
	 *
	 * @param  \Illuminate\Auth\Reminders\RemindableInterface  $user
	 * @return string
	 */
	public function create(RemindableInterface $user);

	/**
	 * Determine if a reminder record exists and is valid.
	 *
	 * @param  \Illuminate\Auth\Reminders\RemindableInterface  $user
	 * @param  string  $token
	 * @return bool
	 */
	public function exists(RemindableInterface $user, $token);

	/**
	 * Determine if a reminder record already exists user and is valid
	 * Return the valid token is exist
	 *
	 * @param  string $email
	 * @return mixed
	 */
	public function requested($email);
	
	/**
	 * Delete a reminder record by token.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function delete($token);

	/**
	 * Delete expired reminders.
	 *
	 * @return void
	 */
	public function deleteExpired();

}
