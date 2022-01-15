<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery as m;

class DatabaseEloquentBroadcastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
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

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Taylor';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                    && count($event->broadcastOn()) === 1
                    && $event->model->name === 'Taylor'
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

        $model = new SoftDeletableTestEloquentBroadcastUser;
        $model->name = 'Bean';
        $model->saveQuietly();

        $model->delete();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof SoftDeletableTestEloquentBroadcastUser
                && $event->event() == 'trashed'
                && count($event->broadcastOn()) === 1
                && $event->model->name === 'Bean'
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.SoftDeletableTestEloquentBroadcastUser.{$event->model->id}";
        });
    }

    public function testBroadcastingForSpecificEventsOnly()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUserOnSpecificEventsOnly;
        $model->name = 'James';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->event() == 'created'
                && count($event->broadcastOn()) === 1
                && $event->model->name === 'James'
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.TestEloquentBroadcastUserOnSpecificEventsOnly.{$event->model->id}";
        });

        $model->name = 'Graham';
        $model->save();

        Event::assertNotDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->model->name === 'Graham'
                && $event->event() == 'updated';
        });
    }

    public function testBroadcastNameDefault()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Mohamed';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                && $event->model->name === 'Mohamed'
                && $event->broadcastAs() === 'TestEloquentBroadcastUserCreated'
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return $eventName === 'TestEloquentBroadcastUserCreated';
                });
        });
    }

    public function testBroadcastNameCanBeDefined()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUserWithSpecificBroadcastName;
        $model->name = 'Nuno';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->model->name === 'Nuno'
                && $event->broadcastAs() === 'foo'
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return $eventName === 'foo';
                });
        });

        $model->name = 'Dries';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->model->name === 'Dries'
                && $event->broadcastAs() === 'TestEloquentBroadcastUserWithSpecificBroadcastNameUpdated'
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return $eventName === 'TestEloquentBroadcastUserWithSpecificBroadcastNameUpdated';
                });
        });
    }

    public function testBroadcastPayloadDefault()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Nuno';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                && $event->model->name === 'Nuno'
                && is_null($event->broadcastWith())
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return Arr::has($payload, ['model', 'connection', 'queue', 'socket']);
                });
        });
    }

    public function testBroadcastPayloadCanBeDefined()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUserWithSpecificBroadcastPayload;
        $model->name = 'Dries';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastPayload
                && $event->model->name === 'Dries'
                && $event->broadcastWith() === ['foo' => 'bar']
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return Arr::has($payload, ['foo', 'socket']);
                });
        });

        $model->name = 'Graham';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastPayload
                && $event->model->name === 'Graham'
                && is_null($event->broadcastWith())
                && $this->assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                    return Arr::has($payload, ['model', 'connection', 'queue', 'socket']);
                });
        });
    }

    private function assertHandldedBroadcastableEvent(BroadcastableModelEventOccurred $event, \Closure $closure)
    {
        $broadcaster = m::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->once()
            ->withArgs(function (array $channels, string $eventName, array $payload) use ($closure) {
                return $closure($channels, $eventName, $payload);
            });

        $manager = m::mock(BroadcastingFactory::class);
        $manager->shouldReceive('connection')->once()->with(null)->andReturn($broadcaster);

        (new BroadcastEvent($event))->handle($manager);

        return true;
    }
}

class TestEloquentBroadcastUser extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';
}

class SoftDeletableTestEloquentBroadcastUser extends Model
{
    use BroadcastsEvents, SoftDeletes;

    protected $table = 'test_eloquent_broadcasting_users';
}

class TestEloquentBroadcastUserOnSpecificEventsOnly extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

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

    public function broadcastWith($event)
    {
        switch ($event) {
            case 'created':
                return ['foo' => 'bar'];
        }
    }
}
