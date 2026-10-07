<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class SessionTypedGettersTest extends TestCase
{
    public function testTypedGettersAreAvailableThroughFacadeHelperAndRequest()
    {
        Route::get('/', function (Request $request) {
            return [
                'facade' => Session::integer('cart.count'),
                'helper' => session()->string('name'),
                'request' => $request->session()->boolean('verified'),
                'collection' => Session::collection('items')->count(),
            ];
        })->middleware('web');

        $this->withSession([
            'cart' => ['count' => 3],
            'name' => 'taylor',
            'verified' => true,
            'items' => ['a', 'b'],
        ])->get('/')->assertExactJson([
            'facade' => 3,
            'helper' => 'taylor',
            'request' => true,
            'collection' => 2,
        ]);
    }

    public function testTypedGettersThrowThroughFacadeOnTypeMismatch()
    {
        Session::put('name', 123);

        $this->expectExceptionObject(new InvalidArgumentException('Session value for key [name] must be a string, integer given.'));

        Session::string('name');
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'array');
    }
}
