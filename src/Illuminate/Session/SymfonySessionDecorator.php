<?php

namespace Illuminate\Session;

use BadMethodCallException;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class SymfonySessionDecorator implements SessionInterface
{
    /**
     * The underlying Laravel session store.
     *
     * @var \Illuminate\Session\Store
     */
    protected $store;

    /**
     * Create a new session decorator.
     *
     * @param  \Illuminate\Session\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Starts the session storage.
     *
     * @throws \RuntimeException if session fails to start
     */
    public function start(): bool
    {
        return $this->store->start();
    }

    /**
     * Returns the session ID.
     */
    public function getId(): string
    {
        return $this->store->getId();
    }

    /**
     * Sets the session ID.
     */
    public function setId(string $id)
    {
        $this->store->setId($id);
    }

    /**
     * Returns the session name.
     */
    public function getName(): string
    {
        return $this->store->getName();
    }

    /**
     * Sets the session name.
     */
    public function setName(string $name)
    {
        $this->store->setName($name);
    }

    /**
     * Invalidates the current session.
     *
     * Clears all session attributes and flashes and regenerates the
     * session and deletes the old session from persistence.
     *
     * @param  int  $lifetime  Sets the cookie lifetime for the session cookie. A null value
     *                         will leave the system settings unchanged, 0 sets the cookie
     *                         to expire with browser session. Time is in seconds, and is
     *                         not a Unix timestamp.
     */
    public function invalidate(int $lifetime = null): bool
    {
        $this->store->invalidate();

        return true;
    }

    /**
     * Migrates the current session to a new session id while maintaining all
     * session attributes.
     *
     * @param  bool  $destroy  Whether to delete the old session or leave it to garbage collection
     * @param  int  $lifetime  Sets the cookie lifetime for the session cookie. A null value
     *                         will leave the system settings unchanged, 0 sets the cookie
     *                         to expire with browser session. Time is in seconds, and is
     *                         not a Unix timestamp.
     */
    public function migrate(bool $destroy = false, int $lifetime = null): bool
    {
        $this->store->migrate($destroy);

        return true;
    }

    /**
     * Force the session to be saved and closed.
     *
     * This method is generally not required for real sessions as
     * the session will be automatically saved at the end of
     * code execution.
     */
    public function save()
    {
        $this->store->save();
    }

    /**
     * Checks if an attribute is defined.
     */
    public function has(string $name): bool
    {
        return $this->store->has($name);
    }

    /**
     * Returns an attribute.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->store->get($name, $default);
    }

    /**
     * Sets an attribute.
     */
    public function set(string $name, mixed $value)
    {
        $this->store->put($name, $value);
    }

    /**
     * Returns attributes.
     */
    public function all(): array
    {
        return $this->store->all();
    }

    /**
     * Sets attributes.
     */
    public function replace(array $attributes)
    {
        $this->store->replace($attributes);
    }

    /**
     * Removes an attribute.
     *
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name): mixed
    {
        return $this->store->remove($name);
    }

    /**
     * Clears all attributes.
     */
    public function clear()
    {
        $this->store->flush();
    }

    /**
     * Checks if the session was started.
     */
    public function isStarted(): bool
    {
        return $this->store->isStarted();
    }

    /**
     * Registers a SessionBagInterface with the session.
     */
    public function registerBag(SessionBagInterface $bag)
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }

    /**
     * Gets a bag instance by name.
     */
    public function getBag(string $name): SessionBagInterface
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }

    /**
     * Gets session meta.
     */
    public function getMetadataBag(): MetadataBag
    {
        throw new BadMethodCallException('Method not implemented by Laravel.');
    }
}
