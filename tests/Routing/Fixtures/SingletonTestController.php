<?php

namespace Illuminate\Tests\Routing\Fixtures;

use Illuminate\Routing\Controller;

class SingletonTestController extends Controller
{
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
