<?php namespace Illuminate\Session;

use SessionHandlerInterface;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CacheBasedSessionHandler implements SessionHandlerInterface, ExpirationAwareInterface {

	use ExpirationAwareTrait;

	/**
	 * The cache repository instance.
	 *
	 * @var \Illuminate\Contracts\Cache\Repository
	 */
	protected $cache;

	/**
	 * Create a new cache driven handler instance.
	 *
	 * @param  \Illuminate\Contracts\Cache\Repository  $cache
	 * @return void
	 */
	public function __construct(CacheContract $cache)
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
	 * @return \Illuminate\Contracts\Cache\Repository
	 */
	public function getCache()
	{
		return $this->cache;
	}

}
