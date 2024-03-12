<?php

namespace Illuminate\Log\Context;

use __PHP_Incomplete_Class;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Log\Context\Events\ContextDehydrating as Dehydrating;
use Illuminate\Log\Context\Events\ContextHydrated as Hydrated;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Throwable;

class Repository
{
    use Macroable, SerializesModels;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * The contextual data.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * The hidden contextual data.
     *
     * @var array<string, mixed>
     */
    protected $hidden = [];

    /**
     * The callback that should handle unserialize exceptions.
     *
     * @var callable|null
     */
    protected static $handleUnserializeExceptionsUsing;

    /**
     * Create a new Context instance.
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if the given key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Determine if the given key exists within the hidden context data.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasHidden($key)
    {
        return array_key_exists($key, $this->hidden);
    }

    /**
     * Retrieve all the context data.
     *
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Retrieve all the hidden context data.
     *
     * @return array<string, mixed>
     */
    public function allHidden()
    {
        return $this->hidden;
    }

    /**
     * Retrieve the given key's value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Retrieve the given key's hidden value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getHidden($key)
    {
        return $this->hidden[$key] ?? null;
    }

    /**
     * Retrieve only the values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only($keys)
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Retrieve only the hidden values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function onlyHidden($keys)
    {
        return array_intersect_key($this->hidden, array_flip($keys));
    }

    /**
     * Add a context value.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function add($key, $value = null)
    {
        $this->data = array_merge(
            $this->data,
            is_array($key) ? $key : [$key => $value]
        );

        return $this;
    }

    /**
     * Add a hidden context value.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHidden($key, $value = null)
    {
        $this->hidden = array_merge(
            $this->hidden,
            is_array($key) ? $key : [$key => $value]
        );

        return $this;
    }

    /**
     * Forget the given context key.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forget($key)
    {
        foreach ((array) $key as $k) {
            unset($this->data[$k]);
        }

        return $this;
    }

    /**
     * Forget the given hidden context key.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forgetHidden($key)
    {
        foreach ((array) $key as $k) {
            unset($this->hidden[$k]);
        }

        return $this;
    }

    /**
     * Add a context value if it does not exist yet.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addIf($key, $value)
    {
        if (! $this->has($key)) {
            $this->add($key, $value);
        }

        return $this;
    }

    /**
     * Add a hidden context value if it does not exist yet.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHiddenIf($key, $value)
    {
        if (! $this->hasHidden($key)) {
            $this->addHidden($key, $value);
        }

        return $this;
    }

    /**
     * Push the given values onto the key's stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function push($key, ...$values)
    {
        if (! $this->isStackable($key)) {
            throw new RuntimeException("Unable to push value onto context stack for key [{$key}].");
        }

        $this->data[$key] = [
            ...$this->data[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Push the given hidden values onto the key's stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function pushHidden($key, ...$values)
    {
        if (! $this->isHiddenStackable($key)) {
            throw new RuntimeException("Unable to push value onto hidden context stack for key [{$key}].");
        }

        $this->hidden[$key] = [
            ...$this->hidden[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Determine if a given key can be used as a stack.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isStackable($key)
    {
        return ! $this->has($key) ||
            (is_array($this->data[$key]) && array_is_list($this->data[$key]));
    }

    /**
     * Determine if a given key can be used as a hidden stack.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isHiddenStackable($key)
    {
        return ! $this->hasHidden($key) ||
            (is_array($this->hidden[$key]) && array_is_list($this->hidden[$key]));
    }

    /**
     * Determine if the repository is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->all() === [] && $this->allHidden() === [];
    }

    /**
     * Execute the given callback when context is about to be dehydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function dehydrating($callback)
    {
        $this->events->listen(fn (Dehydrating $event) => $callback($event->context));

        return $this;
    }

    /**
     * Execute the given callback when context has been hydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function hydrated($callback)
    {
        $this->events->listen(fn (Hydrated $event) => $callback($event->context));

        return $this;
    }

    /**
     * Handle unserialize exceptions using the given callback.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function handleUnserializeExceptionsUsing($callback)
    {
        static::$handleUnserializeExceptionsUsing = $callback;

        return $this;
    }

    /**
     * Flush all context data.
     *
     * @return $this
     */
    public function flush()
    {
        $this->data = [];
        $this->hidden = [];

        return $this;
    }

    /**
     * Dehydrate the context data.
     *
     * @internal
     *
     * @return ?array
     */
    public function dehydrate()
    {
        $instance = (new static($this->events))
            ->add($this->all())
            ->addHidden($this->allHidden());

        $instance->events->dispatch(new Dehydrating($instance));

        $serialize = fn ($value) => serialize($instance->getSerializedPropertyValue($value, withRelations: false));

        return $instance->isEmpty() ? null : [
            'data' => array_map($serialize, $instance->all()),
            'hidden' => array_map($serialize, $instance->allHidden()),
        ];
    }

    /**
     * Hydrate the context instance.
     *
     * @internal
     *
     * @param  ?array  $context
     * @return $this
     */
    public function hydrate($context)
    {
        $unserialize = function ($value, $key, $hidden) {
            try {
                return tap($this->getRestoredPropertyValue(unserialize($value)), function ($value) {
                    if ($value instanceof __PHP_Incomplete_Class) {
                        throw new RuntimeException('Value is incomplete class: '.json_encode($value));
                    }
                });
            } catch (Throwable $e) {
                if (static::$handleUnserializeExceptionsUsing !== null) {
                    return (static::$handleUnserializeExceptionsUsing)($e, $key, $value, $hidden);
                }

                if ($e instanceof ModelNotFoundException) {
                    if (function_exists('report')) {
                        report($e);
                    }

                    return null;
                }

                throw $e;
            }
        };

        [$data, $hidden] = [
            collect($context['data'] ?? [])->map(fn ($value, $key) => $unserialize($value, $key, false))->all(),
            collect($context['hidden'] ?? [])->map(fn ($value, $key) => $unserialize($value, $key, true))->all(),
        ];

        $this->events->dispatch(new Hydrated(
            $this->flush()->add($data)->addHidden($hidden)
        ));

        return $this;
    }
}
