<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class WithEventsTest extends TestCase
{
    protected bool $emailVerification = true;

    protected function tearDown(): void
    {
        // Disabling email verification flips a process-global static on the
        // event service provider, so restore it to keep the opt-out from
        // leaking into the rest of the suite.
        (new ReflectionProperty(EventServiceProvider::class, 'configureEmailVerification'))
            ->setValue(null, true);

        parent::tearDown();
    }

    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withEvents(emailVerification: $this->emailVerification)
            ->create();
    }

    public function testTheEmailVerificationListenerIsRegisteredByDefault()
    {
        $this->assertContains(
            SendEmailVerificationNotification::class,
            $this->app['events']->getRawListeners()[Registered::class] ?? []
        );
    }

    public function testTheEmailVerificationListenerCanBeDisabled()
    {
        $this->emailVerification = false;

        $this->refreshApplication();

        $this->assertNotContains(
            SendEmailVerificationNotification::class,
            $this->app['events']->getRawListeners()[Registered::class] ?? []
        );
    }
}
