<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class EloquentTimestampTest extends DatabaseTestCase
{
    #[DataProvider('dateTimeDataProvider')]
    #[WithMigration]
    public function testItCanGetCorrectDateTimeUsingDefaultFormat($now)
    {
        $user = UserFactory::new()->create([
            'email_verified_at' => $now,
        ])->mergeCasts([
            'email_verified_at' => 'datetime',
        ]);

        $this->assertSame($now, $user->email_verified_at->toDateTimeString());
    }

    public static function dateTimeDataProvider()
    {
        yield ['2023-01-01 00:00:00'];
        yield ['2023-04-01 12:00:00'];
    }
}
