<?php

namespace Illuminate\Tests\Routing\fixtures\Policies;

class PartialFooModelPolicy
{
    public function view($user)
    {
        return true;
    }

    public function create($user)
    {
        return true;
    }

    public function delete($user)
    {
        return true;
    }
}
