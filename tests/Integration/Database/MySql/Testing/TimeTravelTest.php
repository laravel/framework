<?php

namespace Illuminate\Tests\Integration\Database\MySql\Testing;

use Illuminate\Tests\Integration\Database\MySql\MySqlTestCase;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Factories\UserFactory;

class TimeTravelTest extends MySqlTestCase
{
    use WithLaravelMigrations;

    public function testFreezeTimeCanResolveCorrectMicroSecond()
    {
        $this->freezeTime();
        $user = UserFactory::new()->create();

        $this->travel(1)->hour();
        $user->updateTimestamps()->save();

        $this->assertEquals(now(), $user->updated_at);
    }
}
