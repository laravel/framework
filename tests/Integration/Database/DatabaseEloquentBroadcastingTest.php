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

        $model = new TestEloquentBroadcastUserWhenCreatedOnly;
        $model->name = 'James';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWhenCreatedOnly
                && $event->event() == 'created'
                && count($event->broadcastOn()) === 1
                && $event->broadcastOn()[0]->name == "private-Illuminate.Tests.Integration.Database.TestEloquentBroadcastUserWhenCreatedOnly.{$event->model->id}";
        });

        Event::fake([BroadcastableModelEventOccurred::class]);

        $model->update();

        Event::assertNotDispatched(BroadcastableModelEventOccurred::class);

        $model->delete();

        Event::assertNotDispatched(BroadcastableModelEventOccurred::class);
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

    public function testBroadcastNameCanBeSetFromTheModel()
    {
        Event::fake([BroadcastableModelEventOccurred::class]);

        $model = new TestEloquentBroadcastUserWithSpecificBroadcastName;
        $model->name = 'Nuno';
        $model->save();

        Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
                && $event->broadcastAs() === 'foo';
        });
    }

    public function testBroadcastPayloadDefault()
    {
        $model = new TestEloquentBroadcastUser;
        $model->name = 'Dries';

        $this->mock(Broadcaster::class, function (MockInterface $mock) use ($model) {
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

        $model->save();
    }

    public function testCustomBroadcastPayloadCanBeSpecified()
    {
        $model = new TestEloquentBroadcastUserWithSpecificBroadcastPayload;
        $model->name = 'Dries';

        $this->mock(Broadcaster::class, function (MockInterface $mock) use ($model) {
            $mock->shouldReceive('broadcast')
                ->once()
                ->with(
                    m::type('array'),
                    'TestEloquentBroadcastUserWithSpecificBroadcastPayloadCreated',
                    ['foo' => 'bar', 'socket' => null]
                );
        });

        $model->save();
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

class TestEloquentBroadcastUserWhenCreatedOnly extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    public function broadcastOn($event)
    {
        switch ($event)  {
            case 'created':
                return [$this];
            default:
                return [];
        }
    }
}

class TestEloquentBroadcastUserWithSpecificBroadcastName extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    public function broadcastAs()
    {
        return 'foo';
    }
}

class TestEloquentBroadcastUserWithSpecificBroadcastPayload extends Model
{
    use BroadcastsEvents;

    protected $table = 'test_eloquent_broadcasting_users';

    public function broadcastWith()
    {
        return ['foo' => 'bar'];
    }
}
