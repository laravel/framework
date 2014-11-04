<?php namespace Illuminate\Auth;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class DatabaseUserProvider implements UserProviderInterface {

	/**
	 * The active database connection.
	 *
	 * @var \Illuminate\Database\ConnectionInterface
	 */
	protected $conn;

	/**
	 * The hasher implementation.
	 *
	 * @var \Illuminate\Contracts\Hashing\Hasher
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
	 * @param  \Illuminate\Database\ConnectionInterface  $conn
	 * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
	 * @param  string  $table
	 * @return void
	 */
	public function __construct(ConnectionInterface $conn, HasherContract $hasher, $table)
	{
		$this->conn = $conn;
		$this->table = $table;
		$this->hasher = $hasher;
	}

	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $identifier
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function retrieveById($identifier)
	{
		$user = $this->conn->table($this->table)->find($identifier);

		if ( ! is_null($user))
		{
			return new GenericUser((array) $user);
		}
	}

	/**
	 * Retrieve a user by by their unique identifier and "remember me" token.
	 *
	 * @param  mixed   $identifier
	 * @param  string  $token
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function retrieveByToken($identifier, $token)
	{
		$user = $this->conn->table($this->table)
                                ->where('id', $identifier)
                                ->where('remember_token', $token)
                                ->first();

		if ( ! is_null($user))
		{
			return new GenericUser((array) $user);
		}
	}

	/**
	 * Update the "remember me" token for the given user in storage.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  string  $token
	 * @return void
	 */
	public function updateRememberToken(UserContract $user, $token)
	{
		$this->conn->table($this->table)
                            ->where('id', $user->getAuthIdentifier())
                            ->update(['remember_token' => $token]);
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Contracts\Auth\User|null
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
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validateCredentials(UserContract $user, array $credentials)
	{
		$plain = $credentials['password'];

		return $this->hasher->check($plain, $user->getAuthPassword());
	}

}
