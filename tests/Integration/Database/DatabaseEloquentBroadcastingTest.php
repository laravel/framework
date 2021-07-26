<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Mockery\MockInterface;

/**
 * @group integration
 */
class DatabaseEloquentBroadcastingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_eloquent_broadcasting_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function testBasicBroadcasting()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        TestEloquentBroadcastUser::create(['name' => 'Taylor']);

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                    && count($event->broadcastOn()) === 1
                    && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.TestEloquentBroadcastUser.{$event->model->id}";
        });
    }

    public function testChannelRouteFormatting()
    {
        $model = new TestEloquentBroadcastUser;

        $this->assertEquals('Illuminate.Tests.Integration.Database.TestEloquentBroadcastUser.{testEloquentBroadcastUser}', $model->broadcastChannelRoute());
    }

    public function testBroadcastingOnModelTrashing()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = SoftDeletableTestEloquentBroadcastUser::make(['name' => 'Bean']);
        $model->saveQuietly();
        $model->delete();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof SoftDeletableTestEloquentBroadcastUser
                && $event->event() == 'trashed'
                && count($event->broadcastOn()) === 1
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.SoftDeletableTestEloquentBroadcastUser.{$event->model->id}";
        });
    }

    public function testBroadcastingForSpecificEventsOnly()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = TestEloquentBroadcastUserOnSpecificEventsOnly::create(['name' => 'James']);

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->event() == 'created'
                && count($event->broadcastOn()) === 1
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.TestEloquentBroadcastUserOnSpecificEventsOnly.{$event->model->id}";
        });

        $model->update(['name' => 'Graham']);

        Event::assertNotDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->event() == 'updated';
        });

        $model->delete();

        Event::assertNotDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->event() == 'deleted';
        });
    }

    public function testBroadcastNameDefault()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        TestEloquentBroadcastUser::create(['name' => 'Mohamed']);

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                && $event->broadcastAs() === 'TestEloquentBroadcastUserCreated';
        });
    }

    public function testBroadcastNameCanBeDefined()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = TestEloquentBroadcastUserWithSpecificBroadcastName::create(['name' => 'Nuno']);

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->broadcastAs() === 'foo';
        });

        $model->update(['name' => 'Dries']);

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->broadcastAs() === 'TestEloquentBroadcastUserWithSpecificBroadcastNameUpdated';
        });

        $model->delete();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->broadcastAs() === 'TestEloquentBroadcastUserWithSpecificBroadcastNameDeleted';
        });
    }

    public function testBroadcastPayloadDefault()
    {
        $this->mock(Broadcaster::class, function (MockInterface $mock) {
            $mock->shouldReceive('broadcast')
                ->once()
                ->with(
                    m::type('array'),
                    'TestEloquentBroadcastUserCreated',
                    m::on(function ($argument) {
                        return Arr::has($argument, ['model', 'connection', 'queue', 'socket']);
                    })
                );
        });

        TestEloquentBroadcastUser::create(['name' => 'Nuno']);
    }

    public function testBroadcastPayloadCanBeDefined()
    {
        $this->mock(Broadcaster::class, function (MockInterface $mock) {
            $mock->shouldReceive('broadcast')
                ->once()
                ->with(
                    m::type('array'),
                    'TestEloquentBroadcastUserWithSpecificBroadcastPayloadCreated',
                    ['foo' => 'bar', 'socket' => null]
                );

            $mock->shouldReceive('broadcast')
                ->once()
                ->with(
                    m::type('array'),
                    'TestEloquentBroadcastUserWithSpecificBroadcastPayloadUpdated',
                    m::on(function ($argument) {
                        return Arr::has($argument, ['model', 'connection', 'queue', 'socket']);
                    })
                );
        });

        $model = TestEloquentBroadcastUserWithSpecificBroadcastPayload::create(['name' => 'Nuno']);

        $model->update(['name' => 'Dries']);
    }
}

class TestEloquentBroadcastUser extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    protected static $unguarded = true;
}

class SoftDeletableTestEloquentBroadcastUser extends Model
{
    use BroadcastsEvents, SoftDeletes;

    protected $table = 'test_eloquent_broadcasting_users';

    protected static $unguarded = true;
}

class TestEloquentBroadcastUserOnSpecificEventsOnly extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    protected static $unguarded = true;

    public function broadcastOn($event)
    {
        switch ($event) {
            case 'created':
                return [$this];
        }
    }
}

class TestEloquentBroadcastUserWithSpecificBroadcastName extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    protected static $unguarded = true;

    public function broadcastAs($event)
    {
        switch ($event) {
            case 'created':
                return 'foo';
        }
    }
}

class TestEloquentBroadcastUserWithSpecificBroadcastPayload extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    protected static $unguarded = true;

    public function broadcastWith($event)
    {
        switch ($event) {
            case 'created':
                return ['foo' => 'bar'];
        }
    }
}
