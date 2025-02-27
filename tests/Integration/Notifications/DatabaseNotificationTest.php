<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\DefineDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration('laravel', 'notifications')]
class DatabaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[DefineDatabase('defineDatabaseAndConvertUserIdToUuid')]
    public function testAssertSentToWhenNotifiableHasStringableKey()
    {
        Notification::fake();

        $user = UuidUserFactoryStub::new()->create();

        app(Dispatcher::class)->send($user, new NotificationStub);

        Notification::assertSentTo($user, NotificationStub::class, function ($notification, $channels, $notifiable) use ($user) {
            return $notifiable === $user;
        });
    }

    /**
     * Define database and convert User's ID to UUID.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineDatabaseAndConvertUserIdToUuid($app): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
        });
    }
}

class UuidUserFactoryStub extends \Orchestra\Testbench\Factories\UserFactory
{
    protected $model = UuidUserStub::class;
}

class UuidUserStub extends \Illuminate\Foundation\Auth\User
{
    use HasUuids;

    protected $table = 'users';
    public $incrementing = false;
    protected $keyType = 'string';

    public function uniqueIds(): array
    {
        return ['id'];
    }

    #[\Override]
    public function casts()
    {
        return array_merge(parent::casts(), ['id' => AsStringable::class]);
    }
}

class NotificationStub extends \Illuminate\Notifications\Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}
