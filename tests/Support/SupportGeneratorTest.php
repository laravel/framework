<?php

namespace Illuminate\Tests\Support;

use Exception;
use Illuminate\Support\Generator;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

class SupportGeneratorTest extends TestCase
{

    public function testRandom()
    {
        $this->assertEquals(16, strlen(Generator::random()));
        $randomInteger = random_int(1, 100);
        $this->assertEquals($randomInteger, strlen(Generator::random($randomInteger)));
        $this->assertIsString(Generator::random());
    }

    public function testWhetherTheNumberOfGeneratedCharactersIsEquallyDistributed()
    {
        $results = [];
        // take 6.200.000 samples, because there are 62 different characters
        for ($i = 0; $i < 620000; $i++) {
            $random = Generator::random(1);
            $results[$random] = ($results[$random] ?? 0) + 1;
        }

        // each character should occur 100.000 times with a variance of 5%.
        foreach ($results as $result) {
            $this->assertEqualsWithDelta(10000, $result, 500);
        }
    }

    public function testRandomStringFactoryCanBeSet()
    {
        Generator::createRandomStringsUsing(fn ($length) => 'length:'.$length);

        $this->assertSame('length:7', Generator::random(7));
        $this->assertSame('length:7', Generator::random(7));

        Generator::createRandomStringsNormally();

        $this->assertNotSame('length:7', Generator::random());
    }

    public function testItCanSpecifyASequenceOfRandomStringsToUtilise()
    {
        Generator::createRandomStringsUsingSequence([
            0 => 'x',
            // 1 => just generate a random one here...
            2 => 'y',
            3 => 'z',
            // ... => continue to generate random strings...
        ]);

        $this->assertSame('x', Generator::random());
        $this->assertSame(16, mb_strlen(Generator::random()));
        $this->assertSame('y', Generator::random());
        $this->assertSame('z', Generator::random());
        $this->assertSame(16, mb_strlen(Generator::random()));
        $this->assertSame(16, mb_strlen(Generator::random()));

        Generator::createRandomStringsNormally();
    }

    public function testItCanSpecifyAFallbackForARandomStringSequence()
    {
        Generator::createRandomStringsUsingSequence([Generator::random(), Generator::random()], fn () => throw new Exception('Out of random strings.'));
        Generator::random();
        Generator::random();

        try {
            $this->expectExceptionMessage('Out of random strings.');
            Generator::random();
            $this->fail();
        } finally {
            Generator::createRandomStringsNormally();
        }
    }

    public function testUuid()
    {
        $this->assertInstanceOf(UuidInterface::class, Generator::uuid());
        $this->assertInstanceOf(UuidInterface::class, Generator::orderedUuid());
        $this->assertInstanceOf(UuidInterface::class, Generator::uuid7());
    }

    public function testItCanFreezeUuids()
    {
        $this->assertNotSame((string) Generator::uuid(), (string) Generator::uuid());
        $this->assertNotSame(Generator::uuid(), Generator::uuid());

        $uuid = Generator::freezeUuids();

        $this->assertSame($uuid, Generator::uuid());
        $this->assertSame(Generator::uuid(), Generator::uuid());
        $this->assertSame((string) $uuid, (string) Generator::uuid());
        $this->assertSame((string) Generator::uuid(), (string) Generator::uuid());

        Generator::createUuidsNormally();

        $this->assertNotSame(Generator::uuid(), Generator::uuid());
        $this->assertNotSame((string) Generator::uuid(), (string) Generator::uuid());
    }

    public function testItCanFreezeUuidsInAClosure()
    {
        $uuids = [];

        $uuid = Generator::freezeUuids(function ($uuid) use (&$uuids) {
            $uuids[] = $uuid;
            $uuids[] = Generator::uuid();
            $uuids[] = Generator::uuid();
        });

        $this->assertSame($uuid, $uuids[0]);
        $this->assertSame((string) $uuid, (string) $uuids[0]);
        $this->assertSame((string) $uuids[0], (string) $uuids[1]);
        $this->assertSame($uuids[0], $uuids[1]);
        $this->assertSame((string) $uuids[0], (string) $uuids[1]);
        $this->assertSame($uuids[1], $uuids[2]);
        $this->assertSame((string) $uuids[1], (string) $uuids[2]);
        $this->assertNotSame(Generator::uuid(), Generator::uuid());
        $this->assertNotSame((string) Generator::uuid(), (string) Generator::uuid());

        Generator::createUuidsNormally();
    }

    public function testItCreatesUuidsNormallyAfterFailureWithinFreezeMethod()
    {
        try {
            Generator::freezeUuids(function () {
                Generator::createUuidsUsing(fn () => Str::of('1234'));
                $this->assertSame('1234', Generator::uuid()->toString());
                throw new \Exception('Something failed.');
            });
        } catch (\Exception) {
            $this->assertNotSame('1234', Generator::uuid()->toString());
        }
    }

    public function testItCanSpecifyASequenceOfUuidsToUtilise()
    {
        Generator::createUuidsUsingSequence([
            0 => ($zeroth = Generator::uuid()),
            1 => ($first = Generator::uuid7()),
            // just generate a random one here...
            3 => ($third = Generator::uuid()),
            // continue to generate random uuids...
        ]);

        $retrieved = Generator::uuid();
        $this->assertSame($zeroth, $retrieved);
        $this->assertSame((string) $zeroth, (string) $retrieved);

        $retrieved = Generator::uuid();
        $this->assertSame($first, $retrieved);
        $this->assertSame((string) $first, (string) $retrieved);

        $retrieved = Generator::uuid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        $retrieved = Generator::uuid();
        $this->assertSame($third, $retrieved);
        $this->assertSame((string) $third, (string) $retrieved);

        $retrieved = Generator::uuid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        Generator::createUuidsNormally();
    }

    public function testItCanSpecifyAFallbackForASequence()
    {
        Generator::createUuidsUsingSequence([Generator::uuid(), Generator::uuid()], fn () => throw new Exception('Out of Uuids.'));
        Generator::uuid();
        Generator::uuid();

        try {
            $this->expectExceptionMessage('Out of Uuids.');
            Generator::uuid();
            $this->fail();
        } finally {
            Generator::createUuidsNormally();
        }
    }

    public function testItCanFreezeUlids()
    {
        $this->assertNotSame((string) Generator::ulid(), (string) Generator::ulid());
        $this->assertNotSame(Generator::ulid(), Generator::ulid());

        $ulid = Generator::freezeUlids();

        $this->assertSame($ulid, Generator::ulid());
        $this->assertSame(Generator::ulid(), Generator::ulid());
        $this->assertSame((string) $ulid, (string) Generator::ulid());
        $this->assertSame((string) Generator::ulid(), (string) Generator::ulid());

        Generator::createUlidsNormally();

        $this->assertNotSame(Generator::ulid(), Generator::ulid());
        $this->assertNotSame((string) Generator::ulid(), (string) Generator::ulid());
    }

    public function testItCanFreezeUlidsInAClosure()
    {
        $ulids = [];

        $ulid = Generator::freezeUlids(function ($ulid) use (&$ulids) {
            $ulids[] = $ulid;
            $ulids[] = Generator::ulid();
            $ulids[] = Generator::ulid();
        });

        $this->assertSame($ulid, $ulids[0]);
        $this->assertSame((string) $ulid, (string) $ulids[0]);
        $this->assertSame((string) $ulids[0], (string) $ulids[1]);
        $this->assertSame($ulids[0], $ulids[1]);
        $this->assertSame((string) $ulids[0], (string) $ulids[1]);
        $this->assertSame($ulids[1], $ulids[2]);
        $this->assertSame((string) $ulids[1], (string) $ulids[2]);
        $this->assertNotSame(Generator::ulid(), Generator::ulid());
        $this->assertNotSame((string) Generator::ulid(), (string) Generator::ulid());

        Generator::createUlidsNormally();
    }

    public function testItCreatesUlidsNormallyAfterFailureWithinFreezeMethod()
    {
        try {
            Generator::freezeUlids(function () {
                Generator::createUlidsUsing(fn () => Str::of('1234'));
                $this->assertSame('1234', (string) Generator::ulid());
                throw new \Exception('Something failed');
            });
        } catch (\Exception) {
            $this->assertNotSame('1234', (string) Generator::ulid());
        }
    }

    public function testItCanSpecifyASequenceOfUlidsToUtilise()
    {
        Generator::createUlidsUsingSequence([
            0 => ($zeroth = Generator::ulid()),
            1 => ($first = Generator::ulid()),
            // just generate a random one here...
            3 => ($third = Generator::ulid()),
            // continue to generate random ulids...
        ]);

        $retrieved = Generator::ulid();
        $this->assertSame($zeroth, $retrieved);
        $this->assertSame((string) $zeroth, (string) $retrieved);

        $retrieved = Generator::ulid();
        $this->assertSame($first, $retrieved);
        $this->assertSame((string) $first, (string) $retrieved);

        $retrieved = Generator::ulid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        $retrieved = Generator::ulid();
        $this->assertSame($third, $retrieved);
        $this->assertSame((string) $third, (string) $retrieved);

        $retrieved = Generator::ulid();
        $this->assertFalse(in_array($retrieved, [$zeroth, $first, $third], true));
        $this->assertFalse(in_array((string) $retrieved, [(string) $zeroth, (string) $first, (string) $third], true));

        Generator::createUlidsNormally();
    }

    public function testItCanSpecifyAFallbackForAUlidSequence()
    {
        Generator::createUlidsUsingSequence(
            [Generator::ulid(), Generator::ulid()],
            fn () => throw new Exception('Out of Ulids'),
        );
        Generator::ulid();
        Generator::ulid();

        try {
            $this->expectExceptionMessage('Out of Ulids');
            Generator::ulid();
            $this->fail();
        } finally {
            Generator::createUlidsNormally();
        }
    }

    public function testPasswordCreation()
    {
        $this->assertTrue(strlen(Generator::password()) === 32);

        $this->assertStringNotContainsString(' ', Generator::password());
        $this->assertStringContainsString(' ', Generator::password(spaces: true));

        $this->assertTrue(
            Str::of(Generator::password())->contains(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])
        );
    }
}