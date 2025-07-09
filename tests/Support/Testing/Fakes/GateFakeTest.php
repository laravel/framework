<?php

namespace Illuminate\Tests\Support\Testing\Fakes;

use Illuminate\Auth\Access\Gate as AccessGate;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Testing\Fakes\GateFake;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class GateFakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::swap(new AccessGate(new Container, static fn (): null => null));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_fake_can_be_created(): void
    {
        Gate::fake();

        $this->assertInstanceOf(GateFake::class, Gate::getFacadeRoot());
    }

    public function test_allows_method_tracks_ability_checks(): void
    {
        Gate::fake();

        $this->assertTrue(Gate::allows('edit-post'));

        Gate::assertChecked('edit-post');
    }

    public function test_denies_method_tracks_ability_checks(): void
    {
        Gate::fake();

        $this->assertFalse(Gate::denies('edit-post'));

        Gate::assertChecked('edit-post');
    }

    public function test_check_method_with_multiple_abilities(): void
    {
        Gate::fake();

        $this->assertTrue(Gate::check(['edit-post', 'delete-post']));

        Gate::assertChecked('edit-post');
        Gate::assertChecked('delete-post');
    }

    public function test_any_method_with_multiple_abilities(): void
    {
        Gate::fake();

        $this->assertTrue(Gate::any(['edit-post', 'delete-post']));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('delete-post');
    }

    public function test_authorize_method_returns_response(): void
    {
        Gate::fake();

        $response = Gate::authorize('edit-post');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->allowed());

        Gate::assertChecked('edit-post');
    }

    public function test_inspect_method_returns_response(): void
    {
        Gate::fake();

        $response = Gate::inspect('edit-post');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->allowed());

        Gate::assertChecked('edit-post');
    }

    public function test_raw_method_returns_value(): void
    {
        Gate::fake();

        $result = Gate::raw('edit-post');

        $this->assertTrue($result);

        Gate::assertChecked('edit-post');
    }

    public function test_has_checked_returns_true_when_ability_was_checked(): void
    {
        $fake = Gate::fake();

        Gate::allows('edit-post');

        $this->assertTrue($fake->hasChecked('edit-post'));
        $this->assertFalse($fake->hasChecked('delete-post'));
    }

    public function test_checked_method_returns_collection_of_checked_abilities(): void
    {
        $fake = Gate::fake();

        $user = (object) ['id' => 1];

        Gate::allows('edit-post', [$user]);
        Gate::allows('edit-post', [$user]);
        Gate::denies('delete-post');

        $edit = $fake->checked('edit-post');
        $delete = $fake->checked('delete-post');

        $this->assertCount(2, $edit);
        $this->assertCount(1, $delete);
        $this->assertEquals('edit-post', $edit->first()['ability']);
        $this->assertEquals('delete-post', $delete->first()['ability']);
    }

    public function test_assert_not_checked_passes_when_ability_not_called(): void
    {
        Gate::fake();

        Gate::assertNotChecked('edit-post');
    }

    public function test_assert_nothing_checked_passes_when_no_abilities_called(): void
    {
        Gate::fake();

        Gate::assertNothingChecked();
    }

    public function test_assert_checked_times_verifies_exact_call_count(): void
    {
        Gate::fake();

        Gate::allows('edit-post');
        Gate::allows('edit-post');

        Gate::assertCheckedTimes('edit-post', 2);
    }

    public function test_assert_checked_with_numeric_parameter_calls_assert_checked_times(): void
    {
        Gate::fake();

        Gate::allows('edit-post');
        Gate::allows('edit-post');
        Gate::allows('edit-post');

        Gate::assertChecked('edit-post', 3);
    }

    public function test_assert_checked_with_verifies_specific_arguments(): void
    {
        Gate::fake();

        $user = (object) ['id' => 1, 'name' => 'John'];
        $post = (object) ['id' => 1, 'title' => 'Test Post'];

        Gate::allows('edit-post', [$user, $post]);

        Gate::assertCheckedWith('edit-post', $user, $post);
    }

    public function test_assert_checked_with_fails_on_wrong_arguments(): void
    {
        Gate::fake();

        $post = (object) ['id' => 1, 'title' => 'Test Post'];

        Gate::allows('edit-post', [(object) ['id' => 1, 'name' => 'John'], $post]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected ability [edit-post] was not checked with the given arguments.');

        Gate::assertCheckedWith('edit-post', (object) ['id' => 2, 'name' => 'Jane'], $post);
    }

    public function test_assert_checked_in_order_verifies_sequence(): void
    {
        Gate::fake();

        Gate::allows('access-admin');
        Gate::allows('edit-post');
        Gate::allows('delete-post');

        Gate::assertCheckedInOrder(['access-admin', 'edit-post', 'delete-post']);
    }

    public function test_assert_checked_in_order_fails_on_wrong_order(): void
    {
        Gate::fake();

        Gate::allows('edit-post');
        Gate::allows('access-admin');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The abilities were not checked in the expected order.');

        Gate::assertCheckedInOrder(['access-admin', 'edit-post']);
    }

    public function test_assert_checked_in_order_fails_on_missing_ability(): void
    {
        Gate::fake();

        Gate::allows('edit-post');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected ability [access-admin] was not checked.');

        Gate::assertCheckedInOrder(['access-admin', 'edit-post']);
    }

    public function test_assert_checked_for_user_verifies_user_in_arguments(): void
    {
        Gate::fake();

        $user = (object) ['id' => 1, 'name' => 'John'];

        Gate::allows('edit-post', [$user, (object) ['id' => 1, 'title' => 'Test Post']]);

        Gate::assertCheckedForUser($user);
        Gate::assertCheckedForUser($user, 'edit-post');
    }

    public function test_assert_checked_for_user_fails_on_wrong_user(): void
    {
        Gate::fake();

        Gate::allows(
            'edit-post',
            [(object) ['id' => 1, 'name' => 'John'], (object) ['id' => 1, 'title' => 'Test Post']]
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No abilities were checked for user [ID:2] for ability [edit-post]');

        Gate::assertCheckedForUser((object) ['id' => 2, 'name' => 'Jane'], 'edit-post');
    }

    public function test_assert_checked_for_user_fails_on_wrong_ability(): void
    {
        Gate::fake();

        $user = (object) ['id' => 1, 'name' => 'John'];

        Gate::allows('edit-post', [$user, (object) ['id' => 1, 'title' => 'Test Post']]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No abilities were checked for user [ID:1] for ability [delete-post].');

        Gate::assertCheckedForUser($user, 'delete-post');
    }

    public function test_assert_checked_with_callback_filtering(): void
    {
        Gate::fake();

        $user1 = (object) ['id' => 1];
        $user2 = (object) ['id' => 2];

        Gate::allows('edit-post', [$user1]);
        Gate::allows('edit-post', [$user2]);

        Gate::assertChecked('edit-post', static function (string $_ability, array $arguments): bool {
            return isset($arguments[0]) && $arguments[0]->id === 1;
        });
    }

    public function test_assert_checked_with_complex_callback_logic(): void
    {
        Gate::fake();

        $admin = (object) ['id' => 1, 'role' => 'admin'];
        $editor = (object) ['id' => 2, 'role' => 'editor'];
        $post1 = (object) ['id' => 100, 'status' => 'draft'];
        $post2 = (object) ['id' => 101, 'status' => 'published'];

        Gate::allows('edit-post', [$admin, $post1]);
        Gate::allows('edit-post', [$editor, $post2]);
        Gate::allows('delete-post', [$admin, $post1]);

        Gate::assertChecked('edit-post', static function (string $_ability, array $arguments): bool {
            return isset($arguments[0], $arguments[1])
                && $arguments[0]->role === 'admin'
                && $arguments[1]->status === 'draft';
        });
        Gate::assertChecked('delete-post', static function (string $ability, array $arguments): bool {
            return $ability === 'delete-post' && count($arguments) === 2;
        });
    }

    public function test_assert_not_checked_with_callback_filtering(): void
    {
        Gate::fake();

        $user1 = (object) ['id' => 1, 'name' => 'John'];
        $user2 = (object) ['id' => 2, 'name' => 'Jane'];

        Gate::allows('edit-post', [$user1]);
        Gate::allows('edit-post', [$user2]);

        Gate::assertNotChecked('edit-post', static function (string $_ability, array $arguments): bool {
            return isset($arguments[0]) && $arguments[0]->name === 'NonExistent';
        });
    }

    public function test_assert_checked_fails_when_callback_does_not_match(): void
    {
        Gate::fake();

        $user1 = (object) ['id' => 1, 'role' => 'editor'];
        $user2 = (object) ['id' => 2, 'role' => 'admin'];

        Gate::allows('edit-post', [$user1]);
        Gate::allows('edit-post', [$user2]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected ability [edit-post] was not checked.');

        Gate::assertChecked('edit-post', static function (string $_ability, array $arguments): bool {
            return isset($arguments[0]) && $arguments[0]->role === 'manager';
        });
    }

    public function test_selective_faking_with_array(): void
    {
        Gate::fake(['edit-post']);

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertFalse(Gate::allows('admin-only'));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('admin-only');
    }

    public function test_except_method_excludes_abilities_from_faking(): void
    {
        Gate::fake()->except(['admin-only']);

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertFalse(Gate::allows('admin-only'));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('admin-only');
    }

    public function test_fake_with_empty_array_behaves_like_fake_all(): void
    {
        Gate::fake();

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertTrue(Gate::allows('admin-only'));

        Gate::assertChecked('edit-post');
        Gate::assertChecked('admin-only');
    }

    public function test_multiple_except_calls_accumulate(): void
    {
        Gate::fake()
            ->except(['admin-only'])
            ->except(['super-admin']);

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);
        Gate::getFacadeRoot()->define('super-admin', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertFalse(Gate::allows('admin-only'));
        $this->assertFalse(Gate::allows('super-admin'));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('admin-only');
        Gate::assertNotChecked('super-admin');
    }

    public function test_except_with_array_based_faking_still_fakes_if_in_array(): void
    {
        Gate::fake(['edit-post', 'admin-only'])
            ->except(['admin-only']);

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertTrue(Gate::allows('admin-only'));

        Gate::assertChecked('edit-post');
        Gate::assertChecked('admin-only');
    }

    public function test_selective_faking_only_fakes_specified_abilities(): void
    {
        Gate::fake(['edit-post']);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertFalse(Gate::allows('undefined-ability'));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('undefined-ability');
    }

    public function test_check_with_empty_array_returns_true(): void
    {
        Gate::fake();

        $this->assertTrue(Gate::check([]));

        Gate::assertNothingChecked();
    }

    public function test_check_method_with_mixed_ability_results(): void
    {
        Gate::fake(['edit-post']);

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertFalse(Gate::check(['edit-post', 'admin-only']));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('admin-only');
    }

    public function test_check_method_returns_false_when_any_ability_fails(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('allows')->with('first-ability', [])->andReturn(true);
        $gate->shouldReceive('allows')->with('second-ability', [])->andReturn(false);

        $fake = new GateFake($gate);
        $fake->except(['first-ability', 'second-ability']);

        $this->assertFalse($fake->check(['first-ability', 'second-ability']));

        $fake->assertNotChecked('first-ability');
        $fake->assertNotChecked('second-ability');

        $gate->shouldHaveReceived('allows')->with('first-ability', [])->once();
        $gate->shouldHaveReceived('allows')->with('second-ability', [])->once();
    }

    public function test_any_with_empty_array_returns_false(): void
    {
        Gate::fake();

        $this->assertFalse(Gate::any([]));

        Gate::assertNothingChecked();
    }

    public function test_allows_with_empty_string_ability(): void
    {
        Gate::fake();

        $this->assertTrue(Gate::allows(''));

        Gate::assertChecked('');
    }

    public function test_selective_faking_with_empty_string_ability(): void
    {
        Gate::fake(['']);

        $this->assertTrue(Gate::allows(''));
        $this->assertFalse(Gate::allows('undefined-ability'));

        Gate::assertChecked('');
        Gate::assertNotChecked('undefined-ability');
    }

    public function test_except_method_with_string_parameter(): void
    {
        Gate::fake()->except('admin-only');

        Gate::getFacadeRoot()->define('admin-only', static fn (): false => false);

        $this->assertTrue(Gate::allows('edit-post'));
        $this->assertFalse(Gate::allows('admin-only'));

        Gate::assertChecked('edit-post');
        Gate::assertNotChecked('admin-only');
    }

    public function test_has_method_is_forwarded(): void
    {
        Gate::fake();

        Gate::getFacadeRoot()->define('test-ability', static fn (): true => true);

        $this->assertTrue(Gate::has('test-ability'));
        $this->assertFalse(Gate::has('nonexistent-ability'));
    }

    public function test_define_method_is_forwarded(): void
    {
        $fake = Gate::fake();

        $fake->define('custom-ability', static fn (): bool => true);

        $this->assertTrue($fake->has('custom-ability'));
        $this->assertTrue($fake->allows('custom-ability'));
    }

    public function test_policy_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('policy')
            ->once()
            ->with('App\\Models\\Post', 'App\\Policies\\PostPolicy')
            ->andReturnSelf();

        $result = (new GateFake($gate))->policy('App\\Models\\Post', 'App\\Policies\\PostPolicy');

        $this->assertSame($gate, $result);
    }

    public function test_before_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('before')
            ->once()
            ->with($callback = static fn (): true => true)
            ->andReturnSelf();

        $result = (new GateFake($gate))->before($callback);

        $this->assertSame($gate, $result);
    }

    public function test_after_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('after')
            ->once()
            ->with($callback = static fn (): bool => true)
            ->andReturnSelf();

        $result = (new GateFake($gate))->after($callback);

        $this->assertSame($gate, $result);
    }

    public function test_abilities_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('abilities')
            ->once()
            ->andReturn($abilities = ['edit-post' => static fn (): true => true]);

        $result = (new GateFake($gate))->abilities();

        $this->assertSame($abilities, $result);
    }

    public function test_get_policy_for_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('getPolicyFor')
            ->once()
            ->with('App\\Models\\Post')
            ->andReturn($policy = new \stdClass);

        $result = (new GateFake($gate))->getPolicyFor('App\\Models\\Post');

        $this->assertSame($policy, $result);
    }

    public function test_for_user_method_is_forwarded(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('forUser')
            ->once()
            ->with($user = (object) ['id' => 1])
            ->andReturn($mock = m::mock(AccessGate::class));

        $result = (new GateFake($gate))->forUser($user);

        $this->assertSame($mock, $result);
    }

    public function test_complex_argument_matching(): void
    {
        Gate::fake();

        $user = (object) ['id' => 1, 'role' => 'admin'];
        $post = (object) ['id' => 100, 'status' => 'published'];
        $metadata = ['source' => 'api', 'version' => 2];

        Gate::allows('complex-check', [$user, $post, $metadata]);

        Gate::assertCheckedWith('complex-check', $user, $post, $metadata);
    }

    public function test_assert_checked_times_fails_on_wrong_count(): void
    {
        Gate::fake();

        Gate::allows('edit-post');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected ability [edit-post] was checked 1 times instead of 3 times.');

        Gate::assertCheckedTimes('edit-post', 3);
    }

    public function test_assert_nothing_checked_fails_when_abilities_were_checked(): void
    {
        Gate::fake();

        Gate::allows('edit-post');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Abilities were checked unexpectedly.');

        Gate::assertNothingChecked();
    }

    public function test_assert_not_checked_fails_when_ability_was_checked(): void
    {
        Gate::fake();

        Gate::allows('edit-post');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The unexpected ability [edit-post] was checked.');

        Gate::assertNotChecked('edit-post');
    }

    public function test_except_abilities_call_real_gate(): void
    {
        $gate = m::mock(AccessGate::class);
        $gate->shouldReceive('allows')
            ->once()->with('admin-only', [])
            ->andReturn(false);
        $gate->shouldReceive('denies')
            ->once()->with('super-admin', [])
            ->andReturn(true);

        $fake = (new GateFake($gate))->except(['admin-only', 'super-admin']);

        $fake->allows('edit-post');

        $this->assertFalse($fake->allows('admin-only'));
        $this->assertTrue($fake->denies('super-admin'));

        $fake->assertChecked('edit-post');
        $fake->assertNotChecked('admin-only');
        $fake->assertNotChecked('super-admin');
    }

    public function test_real_world_blog_post_workflow(): void
    {
        Gate::fake();

        $admin = (object) ['id' => 1, 'role' => 'admin'];
        $editor = (object) ['id' => 2, 'role' => 'editor'];
        $author = (object) ['id' => 3, 'role' => 'author'];
        $draft = (object) ['id' => 100, 'status' => 'draft', 'author_id' => 3];
        $published = (object) ['id' => 101, 'status' => 'published', 'author_id' => 3];

        Gate::allows('access-admin', [$admin]);
        Gate::check(['edit-post', 'publish-post'], [$editor, $draft]);
        Gate::allows('view-post', [$author, $published]);
        Gate::authorize('delete-post', [$admin, $published]);
        Gate::inspect('moderate-comments', [$editor]);

        Gate::assertChecked('access-admin');
        Gate::assertChecked('edit-post');
        Gate::assertChecked('publish-post');
        Gate::assertChecked('view-post');
        Gate::assertChecked('delete-post');
        Gate::assertChecked('moderate-comments');
        Gate::assertCheckedForUser($admin, 'access-admin');
        Gate::assertCheckedForUser($admin, 'delete-post');
        Gate::assertCheckedForUser($editor, 'edit-post');
        Gate::assertCheckedForUser($author, 'view-post');
        Gate::assertCheckedWith('delete-post', $admin, $published);
        Gate::assertCheckedWith('edit-post', $editor, $draft);
        Gate::assertCheckedInOrder([
            'access-admin',
            'edit-post',
            'publish-post',
            'view-post',
            'delete-post',
            'moderate-comments',
        ]);
    }

    /**
     * @group performance
     */
    public function test_performance_with_large_number_of_ability_checks(): void
    {
        Gate::fake();

        for ($i = 0; $i < 100; $i++) {
            Gate::allows("ability-{$i}", [$user = (object) ['id' => 1]]);
        }

        $this->assertCount(1, Gate::getFacadeRoot()->checked('ability-50'));

        Gate::assertChecked('ability-0');
        Gate::assertChecked('ability-50');
        Gate::assertChecked('ability-99');
        Gate::assertCheckedTimes('ability-50', 1);
        Gate::assertCheckedForUser($user, 'ability-99');
    }

    public function test_fake_properly_resets_between_instances(): void
    {
        Gate::fake();
        Gate::allows('test-ability');

        $this->assertCount(1, ($first = Gate::getFacadeRoot())->checked('test-ability'));

        $first->assertChecked('test-ability');

        Gate::fake();

        $this->assertCount(0, ($second = Gate::getFacadeRoot())->checked('test-ability'));

        $second->assertNotChecked('test-ability');
    }

    public function test_rapid_successive_ability_checks(): void
    {
        Gate::fake();

        for ($i = 0; $i < 50; $i++) {
            Gate::allows('rapid-test');
        }

        Gate::assertCheckedTimes('rapid-test', 50);
    }
}
