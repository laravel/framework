<?php

namespace Illuminate\Tests\Integration\Database;

use Closure;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Broadcasting\SoftDeletableTestEloquentBroadcastUser;
use Illuminate\Tests\App\Models\Broadcasting\TestEloquentBroadcastUser;
use Illuminate\Tests\App\Models\Broadcasting\TestEloquentBroadcastUserOnSpecificEventsOnly;
use Illuminate\Tests\App\Models\Broadcasting\TestEloquentBroadcastUserWithSpecificBroadcastName;
use Illuminate\Tests\App\Models\Broadcasting\TestEloquentBroadcastUserWithSpecificBroadcastPayload;
use Mockery;

class DatabaseEloquentBroadcastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
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
                    && $event->broadcastOn()[0]->name === "private-Illuminate.Tests.App.Models.Broadcasting.TestEloquentBroadcastUser.{$event->model->id}";
        });
    }

    public function testChannelRouteFormatting()
    {
        $model = new TestEloquentBroadcastUser;

        $this->assertSame('Illuminate.Tests.App.Models.Broadcasting.TestEloquentBroadcastUser.{testEloquentBroadcastUser}', $model->broadcastChannelRoute());
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
                && $event->event() === 'trashed'
                && count($event->broadcastOn()) === 1
                && $event->model->name === 'Bean'
                && $event->broadcastOn()[0]->name === "private-Illuminate.Tests.App.Models.Broadcasting.SoftDeletableTestEloquentBroadcastUser.{$event->model->id}";
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
                && $event->event() === 'created'
                && count($event->broadcastOn()) === 1
                && $event->model->name === 'James'
                && $event->broadcastOn()[0]->name === "private-Illuminate.Tests.App.Models.Broadcasting.TestEloquentBroadcastUserOnSpecificEventsOnly.{$event->model->id}";
        });

        $model->name = 'Graham';
        $model->save();

        Event::assertNotDispatched(function (BroadcastableModelEventOccurred $event) {
            return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
                && $event->model->name === 'Graham'
                && $event->event() === 'updated';
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

    private function assertHandldedBroadcastableEvent(BroadcastableModelEventOccurred $event, Closure $closure)
    {
        $broadcaster = Mockery::mock(Broadcaster::class);
        $broadcaster->expects('broadcast')
            ->withArgs(function (array $channels, string $eventName, array $payload) use ($closure) {
                return $closure($channels, $eventName, $payload);
            });

        $manager = Mockery::mock(BroadcastingFactory::class);
        $manager->expects('connection')->with(null)->andReturn($broadcaster);

        (new BroadcastEvent($event))->handle($manager);

        return true;
    }
}
