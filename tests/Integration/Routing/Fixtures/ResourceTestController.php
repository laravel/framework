<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Illuminate\Routing\Controller;

class ResourceTestController extends Controller
{
    public function index()
    {
        return 'resource index';
    }

    public function show($id)
    {
        return 'resource show for '.$id;
    }

    public function create()
    {
        return 'resource create';
    }

    public function store()
    {
        return 'resource store';
    }

    public function edit($id)
    {
        return 'resource edit for '.$id;
    }

    public function update($id)
    {
        return 'resource update for '.$id;
    }

    public function destroy($id)
    {
        return 'resource destroy for '.$id;
    }

    public function restore($id)
    {
        return 'resource restore for '.$id;
    }
}
