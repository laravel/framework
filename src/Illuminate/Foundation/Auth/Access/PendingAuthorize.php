<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Traits\ForwardsCalls;

class PendingAuthorize implements Authorizable
{
    use ForwardsCalls;

    protected array $allowableMethods = [
        'can',
        'canAny',
        'cant',
        'cannot',
        'for',
    ];

    public function __construct(
        protected Authorizable $user,
        protected array $args
    ) {
    }

    public function __call(string $name, array $arguments)
    {
        if (!in_array($name, $this->allowableMethods)) {
            throw new \RuntimeException("Method [{$name}] cannot be called on ".self::class);
        }

        if ($name === 'for') {
            $this->args = array_merge($this->args, $arguments);

            return $this;
        }

        return $this->user->{$name}($arguments[0], array_merge($this->args, array_slice($arguments, 1)));
    }

    public function can($abilities, $arguments = [])
    {
        return $this->__call('can', func_get_args());
    }
}
