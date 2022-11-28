<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Illuminate\Routing\Controller;

class CreatableSingletonTestController extends Controller
{
    public function create()
    {
        return 'singleton create';
    }

    public function store()
    {
        return 'singleton store';
    }

    public function show()
    {
        return 'singleton show';
    }

    public function edit()
    {
        return 'singleton edit';
    }

    public function update()
    {
        return 'singleton update';
    }

    public function destroy()
    {
        return 'singleton destroy';
    }
}
