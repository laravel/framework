<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\DistinctWithin;
use Orchestra\Testbench\TestCase;

class DistinctWithinTest extends TestCase
{
    /** @test */
    public function test_prevents_duplicate_submission()
    {
        // Step 1: Create a fake request
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
        ]);

        // Step 2: Create a storage array for the session handler
        $storage = [];
        $handlerStorage =& $storage;  // create reference

        $sessionHandler = new ArraySessionHandler($handlerStorage);

        // Step 4: Create session store with handler
        $session = new Store('test_session', $sessionHandler);

        // Step 5: Attach the session to request
        $request->setLaravelSession($session);

        // Step 6: Bind request to container
        $this->app->instance('request', $request);

        // Step 7: Clear cache
        Cache::flush();

        // Step 8: Instantiate your rule
        $rule = new DistinctWithin(60, $request); // Inject request for testability

        // Step 9: First submission should pass
        $this->assertTrue($rule->passes('email', 'test@example.com'));

        // Step 10: Duplicate submission should fail
        $this->assertFalse($rule->passes('email', 'test@example.com'));
    }
}
