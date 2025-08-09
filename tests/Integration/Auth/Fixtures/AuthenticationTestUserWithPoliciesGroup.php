<?php

namespace Illuminate\Tests\Integration\Auth\Fixtures;

class AuthenticationTestUserWithPoliciesGroup extends AuthenticationTestUser
{
    public function groupPoliciesBy()
    {
        return 'AuthenticationTestUser';
    }
}
