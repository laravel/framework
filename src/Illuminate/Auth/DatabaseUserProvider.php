<?php namespace Illuminate\Auth;

use Illuminate\Database\Connection;
use Illuminate\Hashing\HasherInterface;

class DatabaseUserProvider implements UserProviderInterface {

	/**
	 * The active database connection.
	 *
	 * @param  \Illuminate\Database\Connection
	 */
	protected $conn;

	/**
	 * The hasher implementation.
	 *
	 * @var \Illuminate\Hashing\HasherInterface
	 */
	protected $hasher;

	/**
	 * The table containing the users.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Create a new database user provider.
	 *
	 * @param  \Illuminate\Database\Connection  $conn
	 * @param  \Illuminate\Hashing\HasherInterface  $hasher
	 * @param  string  $table
	 * @return void
	 */
	public function __construct(Connection $conn, HasherInterface $hasher, $table)
	{
		$this->conn = $conn;
		$this->table = $table;
		$this->hasher = $hasher;
	}

	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $identifier
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveByID($identifier)
	{
		$user = $this->conn->table($this->table)->find($identifier);

		if ( ! is_null($user))
		{
			return new GenericUser((array) $user);
		}
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Auth\UserInterface|null
	 */
	public function retrieveByCredentials(array $credentials)
	{
		// First we will add each credential element to the query as a where clause.
		// Then we can execute the query and, if we found a user, return it in a
		// generic "user" object that will be utilized by the Guard instances.
		$query = $this->conn->table($this->table);

		foreach ($credentials as $key => $value)
		{
			if ( ! str_contains($key, 'password'))
			{
				$query->where($key, $value);
			}
		}

		// Now we are ready to execute the query to see if we have an user matching
		// the given credentials. If not, we will just return nulls and indicate
		// that there are no matching users for these given credential arrays.
		$user = $query->first();

		if ( ! is_null($user))
		{
			return new GenericUser((array) $user);
		}
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validateCredentials(UserInterface $user, array $credentials)
	{
		$plain = $credentials['password'];

		return $this->hasher->check($plain, $user->getAuthPassword());
	}

}