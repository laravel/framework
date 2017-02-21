<?php namespace Illuminate\Session;

use Illuminate\Cache\Repository;

class CacheBasedSessionHandler extends ExpirationAwareSessionHandler {

	/**
	 * The cache repository instance.
	 *
	 * @var \Illuminate\Cache\Repository
	 */
	protected $cache;

	/**
	 * Create a new cache driven handler instance.
	 *
	 * @param  \Illuminate\Cache\Repository  $cache
	 * @return void
	 */
	public function __construct(Repository $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * {@inheritDoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read($sessionId)
	{
		return $this->cache->get($sessionId, '');
	}

	/**
	 * {@inheritDoc}
	 */
	public function write($sessionId, $data)
	{
		return $this->cache->put($sessionId, $data, $this->lifetime);
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy($sessionId)
	{
		return $this->cache->forget($sessionId);
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc($lifetime)
	{
		return true;
	}

	/**
	 * Get the underlying cache repository.
	 *
	 * @return \Illuminate\Cache\Repository
	 */
	public function getCache()
	{
		return $this->cache;
	}

}
