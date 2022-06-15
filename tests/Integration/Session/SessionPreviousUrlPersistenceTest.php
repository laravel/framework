<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class SessionPreviousUrlPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('_test/session-url/home', function () {
            return url()->previous();
        });

        Route::middleware('web')->get('_test/session-url/my-page', function () {
            return 'my-page-content';
        });
    }

    public function testRemembersPreviouslyVisitedUrl()
    {
        $this->get('_test/session-url/my-page')
            ->assertOk()
            ->assertSee('my-page-content');

        $this->get('_test/session-url/home')
            ->assertOk()
            ->assertSee(url('_test/session-url/my-page'));
    }

    public function testIgnoresTurboFrameVisitsFromPreviousUrl()
    {
        $this->withHeaders(['Turbo-Frame' => 'testing-frame'])
            ->get('_test/session-url/my-page')
            ->assertOk()
            ->assertSee('my-page-content');

        $this->get('_test/session-url/home')
            ->assertOk()
            ->assertSee(url(''))
            ->assertDontSee(url('_test/session-url/my-page'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('session.expire_on_close', true);
    }
}
