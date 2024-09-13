<?php

namespace Illuminate\Tests\Integration\Database\Traits;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\Model\DatabaseEloquentIntegrationUniqueUserUUID;

trait EloquentBulkInsertTestTrait
{
    public function testBulkInsertWithNonAutoIncrementingId(): void
    {
        $query = DatabaseEloquentIntegrationUniqueUserUUID::query();
        $now = Carbon::now()->startOfSecond();
        $result = $query->insert([
            ['name' => 'First', 'email' => 'foo@gmail.com', 'birthday' => $now],
            ['name' => 'Second', 'email' => 'bar@gmail.com', 'birthday' => $now],
        ]);

        $this->assertTrue($result);
        $this->assertEquals(2, $query->count());

        $user = $query->where('email', 'foo@gmail.com')->first();
        $this->assertEquals('First', $user->getAttribute('name'));
        $this->assertEquals($now, $user->getAttribute('birthday'));
        $this->assertNotNull($user->getAttribute('created_at'));
        $this->assertNotNull($user->getAttribute('updated_at'));

        $this->expectException(UniqueConstraintViolationException::class);
        $query->insert([
            ['name' => 'New', 'email' => 'foo@gmail.com', 'birthday' => $now],
            ['name' => 'Third', 'email' => 'baz@gmail.com', 'birthday' => $now],
        ]);
    }

    public function testUpsertWithNonAutoIncrementingId(): void
    {
        $query = DatabaseEloquentIntegrationUniqueUserUUID::query();
        $now = Carbon::now()->startOfSecond();
        $result = $query->insert([
            ['name' => 'First', 'email' => 'foo@gmail.com', 'birthday' => $now],
            ['name' => 'Second', 'email' => 'bar@gmail.com', 'birthday' => $now],
        ]);

        $this->assertTrue($result);
        $this->assertEquals(2, $query->count());

        $query->upsert([
            ['name' => 'First', 'email' => 'foo@gmail.com', 'birthday' => $now],
            ['name' => 'Third', 'email' => 'baz@gmail.com', 'birthday' => $now],
        ], ['email'], ['name', 'birthday']);

        $this->assertEquals(3, $query->count());

        $user = $query->where('email', 'foo@gmail.com')->first();
        $this->assertEquals('First', $user->getAttribute('name'));
        $this->assertNotNull($user->getAttribute('created_at'));
        $this->assertNotNull($user->getAttribute('updated_at'));
    }
}
