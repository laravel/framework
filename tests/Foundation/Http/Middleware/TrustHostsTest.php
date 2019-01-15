<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Http\Middleware\TrustHosts;

class TrustHostsTest extends TestCase
{
    public function testItSetTrustHostsToRequest()
    {
        $hosts = ['mysites.com'];

        $request = m::mock(Request::class);
        $request->shouldReceive('setTrustedHosts')->with($hosts)->once();

        $middleware = new TrustHosts();
        $middleware->setTrustedHosts($hosts);
        $middleware->handle($request, function(){
        });
    }
}
