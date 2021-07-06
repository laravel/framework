<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithGate;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class InteractsWithGateTest extends TestCase
{
    use InteractsWithGate, InteractsWithAuthentication;

    protected static $expectedFailMessage = null;

    /** @test */
    public function should_allow_access_to_an_ability()
    {
        $this->actingAs(new TestUser);

        $this->assertFalse(Gate::allows('can-do-something'));

        $this->shouldAllow('can-do-something');

        $this->assertTrue(Gate::allows('can-do-something'));
    }

    /** @test */
    public function should_deny_access_to_an_ability()
    {
        $this->actingAs(new TestUser);

        $this->shouldAllow('can-do-something');

        $this->assertTrue(Gate::allows('can-do-something'));

        $this->shouldDeny('can-do-something');

        $this->assertFalse(Gate::allows('can-do-something'));
    }

    /** @test */
    public function should_allow_access_to_an_ability_for_a_specific_user()
    {
        $this->assertFalse(Gate::forUser(new TestUser)->allows('can-do-something'));

        $this->shouldAllow('can-do-something')->forUser(new TestUser);

        $this->assertTrue(Gate::forUser(new TestUser)->allows('can-do-something'));
    }

    /** @test */
    public function should_deny_access_to_an_ability_for_a_specific_user()
    {
        $this->shouldAllow('can-do-something')->forUser(new TestUser);

        $this->assertTrue(Gate::forUser(new TestUser)->allows('can-do-something'));

        $this->shouldDeny('can-do-something')->forUser(new TestUser);

        $this->assertFalse(Gate::forUser(new TestUser)->allows('can-do-something'));
    }

    /** @test */
    public function should_allow_access_to_an_ability_for_a_guest_user()
    {
        $this->assertFalse(Gate::allows('can-do-something'));

        $permission = $this->shouldAllow('can-do-something');

        $this->assertFalse(Gate::allows('can-do-something'));

        $permission->forGuest();

        $this->assertTrue(Gate::allows('can-do-something'));
    }

    /** @test */
    public function should_deny_access_to_an_ability_for_a_guest_user()
    {
        $this->shouldAllow('can-do-something')->forGuest();

        $this->assertTrue(Gate::allows('can-do-something'));

        $this->shouldDeny('can-do-something')->forGuest();

        $this->assertFalse(Gate::allows('can-do-something'));
    }

    /** @test */
    public function should_allow_access_to_a_policy()
    {
        $this->actingAs(new TestUser);

        $this->assertFalse(Gate::allows('update', new TestModel(['id' => 5])));

        $this->shouldAllow('update', new TestModel(['id' => 5]));

        $this->assertTrue(Gate::allows('update', new TestModel(['id' => 5])));
    }

    /** @test */
    public function should_deny_access_to_a_policy()
    {
        $this->actingAs(new TestUser);

        $this->assertFalse(Gate::allows('update', new TestModel(['id' => 3])));

        $this->shouldAllow('update', new TestModel(['id' => 3]));

        $this->assertTrue(Gate::allows('update', new TestModel(['id' => 3])));
    }

    /** @test */
    public function should_allow_access_to_a_policy_for_a_specific_user()
    {
        $this->assertFalse(Gate::forUser(new TestUser)->allows('delete', new TestModel(['id' => 7])));

        $this->shouldAllow('delete', new TestModel(['id' => 7]))->forUser(new TestUser);

        $this->assertTrue(Gate::forUser(new TestUser)->allows('delete', new TestModel(['id' => 7])));
    }

    /** @test */
    public function should_deny_access_to_a_policy_for_a_specific_user()
    {
        $this->shouldAllow('delete', new TestModel(['id' => 7]))->forUser(new TestUser);

        $this->assertTrue(Gate::forUser(new TestUser)->allows('delete', new TestModel(['id' => 7])));

        $this->shouldDeny('delete', new TestModel(['id' => 7]))->forUser(new TestUser);

        $this->assertFalse(Gate::forUser(new TestUser)->allows('delete', new TestModel(['id' => 7])));
    }

    /** @test */
    public function expects_test_to_fail_if_the_permission_expectation_was_not_used()
    {
        static::$expectedFailMessage = 'The expected ability or policiy checks were not called: can-do-something';

        $this->shouldAllow('can-do-something')->forUser(new TestUser);
    }

    /** @test */
    public function can_set_a_message_when_denying_access_to_the_current_user()
    {
        $this->actingAs(new TestUser);

        $this->shouldDeny('can-do-something')->withMessage('[Reason why you cannot do something]', '[Code:123]');

        $response = Gate::inspect('can-do-something');

        $this->assertSame('[Reason why you cannot do something]', $response->message());
        $this->assertSame('[Code:123]', $response->code());
    }

    public static function fail(string $message = ''): void
    {
        if (static::$expectedFailMessage === $message) {
            static::$expectedFailMessage = null;

            return;
        }

        parent::fail($message);
    }
}

class TestUser extends User
{
}

class TestModel extends Model
{
    protected $guarded = [];
}
