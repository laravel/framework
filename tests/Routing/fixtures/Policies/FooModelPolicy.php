<?php

namespace Illuminate\Tests\Routing\fixtures\Policies;

class FooModelPolicy
{
    public function viewAny($user)
    {
        return true;
    }

    public function view($user)
    {
        return true;
    }

    public function create($user)
    {
        return true;
    }

    public function update($user)
    {
        return true;
    }

    public function delete($user)
    {
        return true;
    }
}
