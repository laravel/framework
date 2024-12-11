<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\ForwardsCalls;

class PendingAuthorization implements Authorizable
{
    use ForwardsCalls;

    /**
     * The methods which can be forwarded to the underlying class.
     *
     * @var list<string>
     */
    protected array $allowableMethods = [
        'can',
        'canAny',
        'cant',
        'cannot',
        'for',
    ];

    /**
     * This Authorizable entity on which to check.
     *
     * @var Authorizable
     */
    protected Authorizable $user;

    /**
     * The entity's policy to check against.
     *
     * @var mixed
     */
    protected $for;

    /**
     * The parameters to forward to the policy.
     *
     * @var list<mixed>
     */
    protected array $params;

    public function __construct(
        Authorizable $user,
        mixed $for,
        array $params = []
    ) {
        $this->user = $user;

        if (is_array($for) && array_is_list($for)) {
            $params = array_slice($for, 1);
            $for = $for[0];
        }

        $this->for = $for;
        $this->params = $params;
    }

    public function __call(string $name, array $arguments)
    {
        if (! in_array($name, $this->allowableMethods)) {
            throw new \RuntimeException("Method [{$name}] cannot be called on ".self::class);
        }

        if ($name === 'for') {
            return new static(
                $this->user,
                $this->for,
                array_merge($this->params, self::flattenArgsArray($arguments))
            );
        }

        $entityAndParams = [$this->for, ...$this->params];

        if (count($arguments) > 1) {
            $entityAndParams = array_merge(
                $entityAndParams,
                self::flattenArgsArray(array_slice($arguments, 1))
            );
        }

        return $this->user->{$name}($arguments[0], $entityAndParams);
    }

    private static function flattenArgsArray($arguments)
    {
        return Arr::flatten($arguments, 1);
    }

    #[\Override]
    public function can($abilities, $arguments = [])
    {
        return $this->__call('can', func_get_args());
    }
}
