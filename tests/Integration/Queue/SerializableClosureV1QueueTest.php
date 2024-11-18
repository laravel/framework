<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

#[WithConfig('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF')]
#[WithMigration]
class SerializableClosureV1QueueTest extends TestCase
{
    use RefreshDatabase;

    /** {@inheritDoc} */
    protected function afterRefreshingDatabase()
    {
        UserFactory::new()->create([
            'id' => 100,
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"uuid":"d9701f94-9e3a-4a02-b3a4-215c35c7e54c","displayName":"Illuminate\\\Tests\\\Integration\\\Queue\\\Fixtures\\\Jobs\\\DeleteUser","job":"Illuminate\\\Queue\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\Tests\\\Integration\\\Queue\\\Fixtures\\\Jobs\\\DeleteUser","command":"O:59:\"Illuminate\\\Tests\\\Integration\\\Queue\\\Fixtures\\\Jobs\\\DeleteUser\":1:{s:4:\"user\";O:45:\"Illuminate\\\Contracts\\\Database\\\ModelIdentifier\":5:{s:5:\"class\";s:31:\"Illuminate\\\Foundation\\\Auth\\\User\";s:2:\"id\";i:100;s:9:\"relations\";a:0:{}s:10:\"connection\";s:7:\"testing\";s:15:\"collectionClass\";N;}}"}}',
            'attempts' => 0,
            'available_at' => 1731919764,
            'created_at' => 1731919764,
        ]);
    }

    public function testItCanProcessQueueFromSerializableClosureV1()
    {
        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
        ]);

        $payload = json_decode(DB::table('jobs')->value('payload'), true);

        $command = unserialize($payload['data']['command']);

        $this->assertInstanceOf(Fixtures\Jobs\DeleteUser::class, $command);
        $this->assertInstanceOf(User::class, $command->user);
    }
}
