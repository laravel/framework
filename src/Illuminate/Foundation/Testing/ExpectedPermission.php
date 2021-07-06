<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class ExpectedPermission
{
    /**
     * @var bool
     */
    protected $result;

    /**
     * The user associated to the expected permission.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * Specifies if the permission should be expected for a guest user.
     *
     * @var bool
     */
    protected $guest = false;

    /**
     * The ability associated to the expected permission.
     *
     * @var string
     */
    protected $ability;

    /**
     * The arguments associated to the expected permission.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $arguments;

    /**
     * The response message that will be returned as part of a Gate inspection response.
     *
     * @var string|null
     */
    protected $message = null;

    /**
     * The response code that will be returned as part of a Gate inspection response.
     *
     * @var string|null
     */
    protected $code = null;

    /**
     * The times the expected permission has been matched.
     *
     * @var int
     */
    protected $matches = 0;

    /**
     * ExpectedPermission constructor.
     *
     * @param  bool  $result
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return void
     */
    public function __construct($result, $user, $ability, $arguments)
    {
        $this->result = $result;
        $this->user = $user;
        $this->ability = $ability;
        $this->arguments = collect($arguments);
    }

    /**
     * Set the expectation for a specific user.
     *
     * @param  Authenticatable  $user
     * @return static
     */
    public function forUser(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the expectation for a guest user.
     *
     * @return static
     */
    public function forGuest()
    {
        $this->guest = true;

        return $this;
    }

    /**
     * Add a message for the allowed or denied response.
     *
     * @param  string  $message
     * @param  string|null  $code
     * @return static
     */
    public function withMessage(string $message, string $code = null)
    {
        $this->message = $message;
        $this->code = $code;

        return $this;
    }

    /**
     * Checks if the permission matches the expected user, ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $expectedUser
     * @param  string  $expectedAbility
     * @param  array  $expectedArguments
     * @return bool
     */
    public function matches($expectedUser, $expectedAbility, $expectedArguments)
    {
        $matched = $this->ability === $expectedAbility
            && $this->matchesUser($expectedUser)
            && $this->matchesAllArguments($expectedArguments);

        if ($matched) {
            $this->matches += 1;
        }

        return $matched;
    }

    /**
     * Checks if the given user matches the expectation of this permission.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $expectedUser
     * @return bool
     */
    protected function matchesUser($expectedUser)
    {
        if ($expectedUser === null) {
            return $this->guest;
        }

        return $this->matchesArgument($this->user, $expectedUser);
    }

    /**
     * Checks if the permission arguments match the expected arguments.
     *
     * @param  array  $expectedArguments
     * @return bool
     */
    protected function matchesAllArguments(array $expectedArguments)
    {
        return $this->arguments->zip($expectedArguments)->every(function ($actualAndExpected) {
            [$actual, $expected] = $actualAndExpected;

            return $this->matchesArgument($actual, $expected);
        });
    }

    /**
     * Check if 2 arguments match. This will use the 'is' helper if both arguments are Eloquent models.
     *
     * @param  mixed  $actual
     * @param  mixed  $expected
     * @return bool
     */
    protected function matchesArgument($actual, $expected)
    {
        if ($actual instanceof Model && $expected instanceof Model) {
            return $expected->is($actual);
        }

        return $actual === $expected;
    }

    /**
     * Returns the ability string.
     *
     * @return string
     */
    public function ability()
    {
        return $this->ability;
    }

    /**
     * Returns the expected result.
     *
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function expectedResult()
    {
        if ($this->message != null || $this->code != null) {
            return new Response($this->result, $this->message, $this->code);
        }

        return $this->result;
    }

    /**
     * Returns true if the permission was matched one or more times, false otherwise.
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->matches > 0;
    }
}
