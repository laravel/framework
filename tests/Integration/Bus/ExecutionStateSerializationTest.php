<?php

namespace Illuminate\Tests\Integration\Bus;

use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class ExecutionStateSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('execution_state_serialization_test_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    public function testStateRestoresModelStepResultsFromTheDatabase()
    {
        $user = ExecutionStateSerializationTestUser::create([
            'email' => 'before@example.com',
        ]);
        $state = new ExecutionState('execution-1');
        $state->recordStepResult('fetch-user', $user, 123);

        $serialized = serialize($state);
        $user->update(['email' => 'after@example.com']);

        $restoredState = unserialize($serialized);
        $restoredUser = $restoredState->resultFor('fetch-user');

        $this->assertInstanceOf(ExecutionStateSerializationTestUser::class, $restoredUser);
        $this->assertSame($user->getKey(), $restoredUser->getKey());
        $this->assertSame('after@example.com', $restoredUser->email);
    }

    public function testStateRestoresNestedModelStepResultsFromTheDatabase()
    {
        $user = ExecutionStateSerializationTestUser::create([
            'email' => 'before@example.com',
        ]);
        $state = new ExecutionState('execution-1');
        $state->recordStepResult('fetch-user', ['user' => $user], 123);

        $serialized = serialize($state);
        $user->update(['email' => 'after@example.com']);

        $restoredState = unserialize($serialized);
        $restoredUser = $restoredState->resultFor('fetch-user')['user'];

        $this->assertInstanceOf(ExecutionStateSerializationTestUser::class, $restoredUser);
        $this->assertSame($user->getKey(), $restoredUser->getKey());
        $this->assertSame('after@example.com', $restoredUser->email);
    }

    public function testStateRestoresModelCollectionsFromTheDatabase()
    {
        $firstUser = ExecutionStateSerializationTestUser::create([
            'email' => 'first-before@example.com',
        ]);
        $secondUser = ExecutionStateSerializationTestUser::create([
            'email' => 'second-before@example.com',
        ]);
        $state = new ExecutionState('execution-1');
        $state->recordStepResult('fetch-users', ExecutionStateSerializationTestUser::all(), 123);

        $serialized = serialize($state);
        $firstUser->update(['email' => 'first-after@example.com']);
        $secondUser->update(['email' => 'second-after@example.com']);

        $restoredState = unserialize($serialized);
        $restoredUsers = $restoredState->resultFor('fetch-users');

        $this->assertSame([
            'first-after@example.com',
            'second-after@example.com',
        ], $restoredUsers->pluck('email')->all());
    }
}

class ExecutionStateSerializationTestUser extends Model
{
    public $table = 'execution_state_serialization_test_users';

    public $guarded = [];

    public $timestamps = false;
}
