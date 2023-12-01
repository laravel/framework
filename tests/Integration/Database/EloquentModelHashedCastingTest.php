<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class EloquentModelHashedCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('hashed_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('password')->nullable();
        });
    }

    public function testHashedWithBcrypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 13);

        $subject = HashedCast::create([
            'password' => 'password',
        ]);

        $this->assertTrue(password_verify('password', $subject->password));
        $this->assertSame('2y', password_get_info($subject->password)['algo']);
        $this->assertSame(13, password_get_info($subject->password)['options']['cost']);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => $subject->password,
        ]);
    }

    public function testNotHashedIfAlreadyHashedWithBcrypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 13);

        $subject = HashedCast::create([
            // "password"; 13 rounds; bcrypt;
            'password' => '$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO',
        ]);

        $this->assertSame('$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO',
        ]);
    }

    public function testNotHashedIfNullWithBrcypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 13);

        $subject = HashedCast::create([
            'password' => null,
        ]);

        $this->assertNull($subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => null,
        ]);
    }

    public function testPassingHashWithHigherCostThrowsExceptionWithBcrypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 10);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; 13 rounds; bcrypt;
            'password' => '$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO',
        ]);
    }

    public function testPassingHashWithLowerCostDoesNotThrowExceptionWithBcrypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 13);

        $subject = HashedCast::create([
            // "password"; 7 rounds; bcrypt;
            'password' => '$2y$07$Ivc2VnUOUFtfdbXFc/Ysu.PgiwAHkDmbZQNR1OpIjKCxTxEfWLP5y',
        ]);

        $this->assertSame('$2y$07$Ivc2VnUOUFtfdbXFc/Ysu.PgiwAHkDmbZQNR1OpIjKCxTxEfWLP5y', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$2y$07$Ivc2VnUOUFtfdbXFc/Ysu.PgiwAHkDmbZQNR1OpIjKCxTxEfWLP5y',
        ]);
    }

    public function testPassingDifferentHashAlgorithmThrowsExceptionWithBcrypt()
    {
        Config::set('hashing.driver', 'bcrypt');
        Config::set('hashing.bcrypt.rounds', 13);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; argon2id;
            'password' => '$argon2i$v=19$m=1024,t=2,p=2$OENON0I5bXo2WDQyQnM2bg$3ma8cKHITsmAjyIYKDLdSvtkMCiEz/s6qWnLAf+Ehek',
        ]);
    }

    public function testHashedWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $subject = HashedCast::create([
            'password' => 'password',
        ]);

        $this->assertTrue(password_verify('password', $subject->password));
        $this->assertSame('argon2i', password_get_info($subject->password)['algo']);
        $this->assertSame(1234, password_get_info($subject->password)['options']['memory_cost']);
        $this->assertSame(2, password_get_info($subject->password)['options']['threads']);
        $this->assertSame(7, password_get_info($subject->password)['options']['time_cost']);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => $subject->password,
        ]);
    }

    public function testNotHashedIfAlreadyHashedWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $subject = HashedCast::create([
            // "password"; 1234 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=1234,t=7,p=2$Lm9vSkJuU3M1SllaaTNwZA$5izrDfbWtpkSBH9EczQ8U1yjSOvAkhE4AuYrbBHwi5k',
        ]);

        $this->assertSame('$argon2i$v=19$m=1234,t=7,p=2$Lm9vSkJuU3M1SllaaTNwZA$5izrDfbWtpkSBH9EczQ8U1yjSOvAkhE4AuYrbBHwi5k', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$argon2i$v=19$m=1234,t=7,p=2$Lm9vSkJuU3M1SllaaTNwZA$5izrDfbWtpkSBH9EczQ8U1yjSOvAkhE4AuYrbBHwi5k',
        ]);
    }

    public function testNotHashedIfNullWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $subject = HashedCast::create([
            'password' => null,
        ]);

        $this->assertNull($subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => null,
        ]);
    }

    public function testPassingHashWithHigherMemoryThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; 2345 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);
    }

    public function testPassingHashWithHigherTimeThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; 1234 memory; 2 threads; 8 time; argon2i;
            'password' => '$argon2i$v=19$m=1234,t=8,p=2$LmszcGVHd0t6b3JweUxqTQ$sdY25X0Qe86fezr1cEjYQxAHI2SdN67yVs5x0ovffag',
        ]);
    }

    public function testPassingHashWithHigherThreadsThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 1234);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; 1234 memory; 3 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=1234,t=7,p=3$OFludXF6bzFpRmdpSHdwSA$J1P4dCGJde6mYe2RZEOFWaztBbDWfxQAM09ZQRMjsw8',
        ]);
    }

    public function testPassingHashWithLowerMemoryThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 3456);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $subject = HashedCast::create([
            // "password"; 2345 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);

        $this->assertSame('$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);
    }

    public function testPassingHashWithLowerTimeThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 2345);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 8);

        $subject = HashedCast::create([
            // "password"; 2345 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);

        $this->assertSame('$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);
    }

    public function testPassingHashWithLowerThreadsThrowsExceptionWithArgon()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.argon.memory', 2345);
        Config::set('hashing.argon.threads', 3);
        Config::set('hashing.argon.time', 7);

        $subject = HashedCast::create([
            // "password"; 2345 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);

        $this->assertSame('$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);
    }

    public function testPassingDifferentHashAlgorithmThrowsExceptionWithArgonAndBcrypt()
    {
        Config::set('hashing.driver', 'argon');
        Config::set('hashing.bcrypt.rounds', 13);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; bcrypt;
            'password' => '$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO',
        ]);
    }

    public function testPassingDifferentHashAlgorithmThrowsExceptionWithArgon2idAndBcrypt()
    {
        Config::set('hashing.driver', 'argon2id');
        Config::set('hashing.argon.memory', 2345);
        Config::set('hashing.argon.threads', 2);
        Config::set('hashing.argon.time', 7);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        $subject = HashedCast::create([
            // "password"; 2345 memory; 2 threads; 7 time; argon2i;
            'password' => '$argon2i$v=19$m=2345,t=7,p=2$MWVVZnpiZHl5RkcveHovcA$QECQzuQ2aAKvUpD25cTUJaAyPFxlOIsCRu+5nbDsU3k',
        ]);
    }
}

class HashedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'password' => 'hashed',
    ];
}
