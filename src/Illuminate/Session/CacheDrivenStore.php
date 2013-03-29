<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Response;

class CacheDrivenStore extends Store {

	/**
	 * The cache store instance.
	 *
	 * @var \Illuminate\Cache\Repository
	 */
	protected $cache;

	/**
	 * Create a new Memcache session instance.
	 *
	 * @param  \Illuminate\Cache\Repository  $cache
	 * @return void
	 */
	public function __construct(\Illuminate\Cache\Repository $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @return array|null
	 */
	public function retrieveSession($id)
	{
		return $this->cache->get($id);
	}

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function createSession($id, array $session, Response $response)
	{
		$this->cache->forever($id, $session);
	}

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function updateSession($id, array $session, Response $response)
	{
		return $this->createSession($id, $session, $response);
	}

}