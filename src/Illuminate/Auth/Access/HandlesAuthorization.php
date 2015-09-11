<?php

namespace Illuminate\Auth\Access;

trait HandlesAuthorization
{
    /**
     * Create a new admission instance.
     *
     * @return \Illuminate\Auth\Access\Admission
     */
    protected function allow($reason = null)
    {
        return new Admission($reason);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param  string  $reason
     * @return void
     *
     * @throws \Illuminate\Auth\Access\UnauthorizedException
     */
    protected function deny($reason = 'This action is unauthorized.')
    {
        throw new UnauthorizedException($reason);
    }
}
