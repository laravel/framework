<?php

namespace Illuminate\Support\Traits;

trait HasBootableTraits
{
    /**
     * The array of booted instances.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of trait initializers that will be called on each new instance.
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->bootIfNotBooted();

        $this->initializeTraits();
    }

    /**
     * Get the event method name.
     *
     * @return string
     */
    protected static function getBootEventMethod()
    {
        return static::$bootEventMethod ?? 'fireEvent';
    }

    /**
     * Check if the instance needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->{static::getBootEventMethod()}('booting', false);

            static::booting();
            static::boot();
            static::booted();

            $this->{static::getBootEventMethod()}('booted', false);
        }
    }

    /**
     * Fire the given event for the instance.
     *
     * @param string $event
     * @param bool   $halt
     *
     * @return mixed
     */
    protected function fireEvent($event, $halt = true)
    {
        //
    }

    /**
     * Perform any actions required before the instance boots.
     *
     * @return void
     */
    protected static function booting()
    {
        //
    }

    /**
     * Bootstrap the instance and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the instance.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        $booted = [];

        static::$traitInitializers[$class] = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot'.class_basename($trait);

            if (method_exists($class, $method) && ! in_array($method, $booted)) {
                forward_static_call([$class, $method]);

                $booted[] = $method;
            }

            if (method_exists($class, $method = 'initialize'.class_basename($trait))) {
                static::$traitInitializers[$class][] = $method;

                static::$traitInitializers[$class] = array_unique(
                    static::$traitInitializers[$class]
                );
            }
        }
    }

    /**
     * Perform any actions required after the instance boots.
     *
     * @return void
     */
    protected static function booted()
    {
        //
    }

    /**
     * Initialize any initializable traits on the instance.
     *
     * @return void
     */
    protected function initializeTraits()
    {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }

    protected static function clearBooted(): void
    {
        static::$booted = [];
    }

    /**
     * When a instance is being unserialized, check if it needs to be booted.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();

        $this->initializeTraits();
    }
}
