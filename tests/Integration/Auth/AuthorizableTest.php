<?php

namespace Illuminate\Tests\Integration\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Tests\Integration\Auth\Fixtures\AuthenticationTestUser;
use Orchestra\Testbench\TestCase;

class AuthorizableTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['config']->set(['auth.providers.users.model' => AuthenticationTestUser::class]);
        Gate::policy(Book::class, BookPolicy::class);
    }

    public function test_user_can(): void
    {
        $user = new AuthenticationTestUser();

        $this->assertTrue($user->can('view', new Book()));
        $this->assertTrue($user->can('view', [new Book()]));
        $this->assertTrue($user->can('multipleParams', [new Book(), new Library()]));
        $this->assertTrue($user->can('multipleParamsWithArray', [new Book(), new Library(), ['CS Lewis']]));
    }

    public function test_PendingAuthorization_with_single_parameter(): void
    {
        $user = new AuthenticationTestUser();

        $pendingAuthorization = $user->for(new Book());

        $this->assertTrue($pendingAuthorization->can('view'));
        $this->assertTrue($pendingAuthorization->canAny(['view', 'nonExistent']));
        $this->assertFalse($pendingAuthorization->cant('view'));
        $this->assertFalse($pendingAuthorization->cannot('view'));
    }

    public function test_PendingAuthorization_with_multiple_parameters(): void
    {
        $user = new AuthenticationTestUser();

        $pendingAuthorization = $user->for(new Book());

        $this->assertTrue($pendingAuthorization->can('multipleParams', new Library()));

        $this->assertTrue($pendingAuthorization->canAny('multipleParams', new Library()));
        $this->assertFalse($pendingAuthorization->cant('view'));
        $this->assertFalse($pendingAuthorization->cannot('view'));
    }

    public function test_PendingAuthorization_merges_parameters(): void
    {
        $user = new AuthenticationTestUser();

        $pendingAuthorization = $user->for(new Book());

        $this->assertTrue($pendingAuthorization->can('multipleParams', new Library()));
        $this->assertTrue($pendingAuthorization->can('multipleParams', [new Library()]));
        $this->assertTrue($pendingAuthorization->can('multipleParamsWithArray', [new Library(), ['CS Lewis']]));
    }

    public function test_can_chain_calls(): void
    {
        $pendingAuthorization = (new AuthenticationTestUser())
            ->for(new Book())
            ->for(new Library());

        $this->assertTrue($pendingAuthorization->can('multipleParams'));
        $this->assertTrue($pendingAuthorization->can('multipleParamsWithArray', [['CS Lewis']]));

    }

    public function test_for_can_receive_array_of_arguments(): void
    {
        $user = new AuthenticationTestUser();
        $pendingAuthorization = $user->for([new Book(), new Library()]);

        $this->assertTrue($pendingAuthorization->can('multipleParams'));
    }

    public function test_PendingAuthorization_does_not_forward_non_authorizable_calls(): void
    {
        $user = new AuthenticationTestUser();

        $pendingAuthorization = $user->for(new Book());

        try {
            $pendingAuthorization->getAttributes();
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                "Method [getAttributes] cannot be called on Illuminate\Foundation\Auth\Access\PendingAuthorize",
                $exception->getMessage()
            );
        }
    }
}

class BookPolicy
{
    public function viewAny(AuthenticationTestUser $user)
    {
        return true;
    }

    public function view(AuthenticationTestUser $user, Book $book)
    {
        return true;
    }

    public function multipleParams(AuthenticationTestUser $user, Book $book, Library $library)
    {
        return true;
    }

    public function multipleParamsWithArray(AuthenticationTestUser $user, Book $book, Library $library, array $properties)
    {
        return in_array('CS Lewis', $properties);
    }
}

class Book extends Model {}
class Library extends Model {}
