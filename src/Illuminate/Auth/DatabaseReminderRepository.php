<?php namespace Illuminate\Auth;

use DateTime;
use Illuminate\Database\Connection;

class DatabaseReminderRepository implements ReminderRepositoryInterface {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The reminder database table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The hashing key.
	 *
	 * @var string
	 */
	protected $hashKey;

	/**
	 * Create a new reminder repository instance.
	 *
	 * @var Illuminate\Database\Connection  $connection
	 * @return void
	 */
	public function __construct(Connection $connection, $table, $hashKey)
	{
		$this->table = $table;
		$this->hashKey = $hashKey;
		$this->connection = $connection;
	}

	/**
	 * Create a new reminder record and token.
	 *
	 * @param  Illuminate\Auth\RemindableInterface  $user
	 * @return string
	 */
	public function create(RemindableInterface $user)
	{
		$email = $user->getReminderEmail();

		$token = $this->createNewToken($user);

		$payload = array('email' => $email, 'token' => $token, 'created_at' => new DateTime);

		$this->getTable()->insert($payload);
	}

	/**
	 * Determine if a reminder record exists and is valid.
	 *
	 * @param  Illuminate\Auth\RemindableInterface  $user
	 * @param  string  $token
	 * @return bool
	 */
	public function exists(RemindableInterface $user, $token)
	{
		$email = $user->getReminderEmail();

		return $this->getTable()->where('email', $email)->where('token', $token)->exists();
	}

	/**
	 * Delete a reminder record by token.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function delete($token)
	{
		$this->getTable()->where('token', $token)->delete();
	}

	/**
	 * Create a new token for the user.
	 *
	 * @param  Illuminate\Auth\RemindableInterface  $user
	 * @return string
	 */
	protected function createNewToken(RemindableInterface $user)
	{
		$email = $user->getReminderEmail();

		$value = str_shuffle(sha1($email.spl_object_hash($this).microtime(true)));

		return hash_hmac('sha512', $value, $this->hashKey);
	}

	/**
	 * Begin a new database query against the table.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	protected function getTable()
	{
		return $this->connection->table($this->table);
	}

}