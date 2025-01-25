<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Queue\OnConnection;
use Illuminate\Foundation\Queue\OnQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NotificationQueueAndConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
    }

    #[DataProvider('notificationWithAttributesDataProvider')]
    public function testNotificationWithAttributesRespectsAttributes(
        string $className,
        string $expectedConnection,
        string $expectedQueue
    ) {
        config(["queue.connections.{$expectedConnection}" => ['driver' => 'sync']]);

        Queue::before(function (JobProcessing $jobProcessing) use ($expectedConnection, $expectedQueue) {
            $this->assertSame($expectedConnection, $jobProcessing->connectionName);
            $actualQueue = (new \ReflectionProperty($jobProcessing->job::class,
                'queue'))->getValue($jobProcessing->job);
            $this->assertSame($expectedQueue, $actualQueue);
        });

        (new UserStub())->notify(new $className);
    }

    /**
     * @return array<string, array>
     */
    public static function notificationWithAttributesDataProvider(): array
    {
        return [
            'string attribute values' => [
                NotificationQueueAndConnectionTestWithStringAttributesNotification::class, 'connection-string', 'queue-string',
            ],
            'enum attribute values' => [
                NotificationQueueAndConnectionTestWithEnumAttributesNotification::class, 'my_connection_name', 'my_queue_name',
            ],
            'prefers class properties over attributes' => [
                NotificationQueueAndConnectionTestPrefersPropertiesNotification::class, 'connection_from_constructor', 'queue_from_constructor',
            ],
            'can set queue and connection properties as enums' => [
                NotificationQueueAndConnectionTestCanSetConnectionAndQueueAsEnums::class, 'my_connection_name', 'my_queue_name',
            ],
        ];
    }
}

class NotificationStub extends Notification implements ShouldQueue
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}

#[OnConnection('connection-string')]
#[OnQueue('queue-string')]
class NotificationQueueAndConnectionTestWithStringAttributesNotification extends NotificationStub
{
    use Queueable;
}
#[OnConnection(NotificationQueueAndConnectionTestEnum::my_connection_name)]
#[OnQueue(NotificationQueueAndConnectionTestEnum::my_queue_name)]
class NotificationQueueAndConnectionTestWithEnumAttributesNotification extends NotificationStub
{
    use Queueable;
}

#[OnConnection(NotificationQueueAndConnectionTestEnum::my_connection_name)]
#[OnQueue(NotificationQueueAndConnectionTestEnum::my_queue_name)]
class NotificationQueueAndConnectionTestPrefersPropertiesNotification extends NotificationStub
{
    use Queueable;

    public function __construct()
    {
        $this->onConnection('connection_from_constructor');
        $this->onQueue('queue_from_constructor');
    }
}

class NotificationQueueAndConnectionTestCanSetConnectionAndQueueAsEnums extends NotificationStub
{
    use Queueable;

    public function __construct()
    {
        $this->queue = NotificationQueueAndConnectionTestEnum::my_queue_name;
        $this->connection = NotificationQueueAndConnectionTestEnum::my_connection_name;
    }
}

class UserStub extends Model
{
    use Notifiable;

    protected $table = 'users';
}

enum NotificationQueueAndConnectionTestEnum
{
    case my_queue_name;
    case my_connection_name;
}
