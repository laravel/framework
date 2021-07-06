<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\ExpectedPermission;
use Illuminate\Support\Facades\Gate;

trait InteractsWithGate
{
    /**
     * Indicates if the hooks have been registered.
     *
     * @var bool
     */
    private $hasRegisteredGateHooks = false;

    /**
     * Collection of expected permissions.
     *
     * @var \Illuminate\Support\Collection
     */
    private $expectedPermissions;

    /**
     * Set the expectation for the given ability and allows access.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Testing\ExpectedPermission
     */
    protected function shouldAllow($ability, ...$arguments)
    {
        return $this->setExpectedPermission(true, auth()->user(), $ability, $arguments);
    }

    /**
     * Set the expectation for the given ability and denies access.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Testing\ExpectedPermission
     */
    protected function shouldDeny($ability, ...$arguments)
    {
        return $this->setExpectedPermission(false, auth()->user(), $ability, $arguments);
    }

    /**
     * Add an expected permission to the list of expected permissions.
     *
     * @param  bool  $result
     * @param  Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return \Illuminate\Foundation\Testing\ExpectedPermission
     */
    private function setExpectedPermission($result, $user, $ability, array $arguments)
    {
        $this->registerGateHooks();

        return tap(new ExpectedPermission($result, $user, $ability, $arguments), function ($permission) {
            $this->expectedPermissions->prepend($permission);
        });
    }

    /**
     * Register the gate hooks and expectations. Also initializes the expected permissions collection.
     *
     * @return void
     */
    private function registerGateHooks()
    {
        if ($this->hasRegisteredGateHooks) {
            return;
        }

        $this->expectedPermissions = collect();

        Gate::before(function ($user = null, $ability, $arguments) {
            if ($permission = $this->expectedPermission($user, $ability, $arguments)) {
                return $permission->expectedResult();
            }

            return null;
        });

        $this->beforeApplicationDestroyed(function () {
            $unmatched = $this->expectedPermissions->reject(function ($permision) {
                return $permision->wasMatched();
            })->map(function ($permission) {
                return $permission->ability();
            });

            if ($unmatched->isNotEmpty()) {
                $this->fail(sprintf('The expected ability or policiy checks were not called: %s', $unmatched->join(', ')));
            }
        });

        $this->hasRegisteredGateHooks = true;
    }

    /**
     * Gets the expected permission from the list of expected permissions.
     *
     * @param  Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return \Illuminate\Foundation\Testing\ExpectedPermission|null
     */
    private function expectedPermission($user, $ability, $arguments)
    {
        return $this->expectedPermissions->first(function (ExpectedPermission $permission) use ($user, $ability, $arguments) {
            return $permission->matches($user, $ability, $arguments);
        });
    }
}
