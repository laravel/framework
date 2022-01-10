<?php

namespace Illuminate\Tests\Routing\fixtures;

use Illuminate\Attributes\Routing\Middleware;
use Illuminate\Routing\Controller;

#[Middleware('one')]
class MiddlewareByAttributeController extends Controller
{
    #[Middleware('two', ['arg1', 'arg2'])]
    public function index()
    {
    }
}
