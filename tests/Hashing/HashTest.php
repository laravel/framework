<?php

namespace Illuminate\Tests\Hashing;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;

class HashTest extends TestCase
{
    public function testSha1HashIsConsistentForSameInput()
    {
        $email = 'taylor@laravel.com';
        $timeStamp = Carbon::now();

        $firstHash = sha1($email.$timeStamp);
        $secondHash = sha1($email.$timeStamp);

        $this->assertTrue(hash_equals($firstHash, $secondHash));
    }
}
