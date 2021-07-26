<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Broadcasting\BroadcastEvent;
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

/**
 * @group integration
 */
class DatabaseEloquentBroadcastingTest extends DatabaseTestCase
{
    protected $broadcaster;

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

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Taylor';
        $model->save();

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

        $model = new SoftDeletableTestEloquentBroadcastUser;
        $model->name = 'Bean';
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

        $model = new TestEloquentBroadcastUserOnSpecificEventsOnly;
        $model->name = 'James';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->event() == 'created'
                && count($event->broadcastOn()) === 1
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.TestEloquentBroadcastUserOnSpecificEventsOnly.{$event->model->id}";
        });

        $model->name = 'Graham';
        $model->save();

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

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Mohamed';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                && $event->broadcastAs() === 'TestEloquentBroadcastUserCreated';
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
                && $event->broadcastAs() === 'foo';
        });

        $model->name = 'Dries';
        $model->save();

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
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUser;
        $model->name = 'Nuno';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUser
                && $event->model->name === 'Nuno'
                && is_null($event->broadcastWith())
                && $this->assertHandldedBroadcastableModelEventHasDefaultPayload($event);
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
                && Arr::get($event->broadcastWith(), 'foo') === 'bar';
        });

        $model->name = 'Graham';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastPayload
                && $event->model->name === 'Graham'
                && is_null($event->broadcastWith())
                && $this->assertHandldedBroadcastableModelEventHasDefaultPayload($event);
        });
    }

    private function assertHandldedBroadcastableModelEventHasDefaultPayload(BroadcastableModelEventOccurred $event)
    {
        $broadcaster = m::mock(Broadcaster::class)
            ->shouldReceive('broadcast')
            ->once()
            ->withArgs(function (array $channels, $eventName, array $payload) use ($event) {
                return Arr::has($payload, ['model', 'connection', 'queue', 'socket'])
                    && Arr::get($payload, 'model.id') === $event->model->id;
            })->getMock();

        (new BroadcastEvent($event))->handle($broadcaster);

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
