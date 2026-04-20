<?php

namespace Illuminate\Tests\Hashing;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Hashing\ArgonHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Hashing\HashManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HasherTest extends TestCase
{
    /**
     * The hash manager instance used across tests.
     *
     * @var HashManager
     */
    public $hashManager;

    /**
     * Bootstrap a minimal container with config binding
     * so HashManager behaves like in a real application context.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::setInstance(new Container);

        $container->singleton(
            /*abstract:*/ 'config',
            /*concrete:*/ fn() => new Config()
        );

        $this->hashManager = new HashManager(/*container: */ $container);
    }

    /**
     * Provide all supported hasher implementations.
     *
     * Used to ensure consistent behavior across algorithms.
     *
     * @return array<array{0: BcryptHasher|ArgonHasher|Argon2IdHasher}>
     */
    public static function hasherProvider(): array
    {
        return [
            [new BcryptHasher()],
            [new ArgonHasher()],
            [new Argon2IdHasher()],
        ];
    }

    /**
     * Provide hashers with expected algorithm names and baseline options.
     *
     * This ensures:
     * - The correct algorithm is used
     * - Default security parameters are respected
     *
     * @return array<array{0: BcryptHasher|ArgonHasher|Argon2IdHasher, 1: string, 2: array<string, int>}>
     */
    public static function basicHashingProvider(): array
    {
        return [
            [new BcryptHasher(), 'bcrypt', ['cost' => PASSWORD_BCRYPT_DEFAULT_COST]],
            [
                new ArgonHasher(), 'argon2i', [
                    'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    'threads' => PASSWORD_ARGON2_DEFAULT_THREADS
                ]
            ],
            [
                new Argon2IdHasher(), 'argon2id', [
                    'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                    'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
                    'threads' => PASSWORD_ARGON2_DEFAULT_THREADS
                ]
            ],
        ];
    }

    /**
     * Provide hasher pairs to validate cross-algorithm verification failures.
     *
     * Each pair represents:
     * - A hasher attempting validation
     * - A different hasher generating the hash
     *
     * Expectation: verification must fail with RuntimeException
     *
     * @return array<array{0: BcryptHasher|ArgonHasher|Argon2IdHasher, 1: BcryptHasher|ArgonHasher|Argon2IdHasher}>
     */
    public static function crossAlgorithmProvider(): array
    {
        return [
            [new BcryptHasher(/*options:*/ ['verify' => true]), new ArgonHasher(/*options:*/ ['verify' => true])],
            [new ArgonHasher(/*options:*/ ['verify' => true]), new BcryptHasher(/*options:*/ ['verify' => true])],
            [new Argon2IdHasher(/*options:*/ ['verify' => true]), new BcryptHasher(/*options:*/ ['verify' => true])],
        ];
    }

    /**
     * Provide invalid hasher configurations that should not be supported.
     *
     * These configurations intentionally break security guarantees
     * (e.g. zero cost/time), so hashing must throw a RuntimeException.
     *
     * @return array<array{0: BcryptHasher|ArgonHasher|Argon2IdHasher}>
     */
    public static function unsupportedConfigurationProvider(): array
    {
        return [
            [new BcryptHasher(/*options:*/ ['rounds' => 0])],
            [new ArgonHasher(/*options:*/ ['time' => 0])],
            [new Argon2IdHasher(/*options:*/ ['time' => 0])],
        ];
    }

    /**
     * Provide hashing drivers and their expected hasher implementations.
     *
     * @return array<array{0: string, 1: class-string}>
     */
    public static function hashManagerDriverProvider(): array
    {
        return [
            ['bcrypt', BcryptHasher::class],
            ['argon', ArgonHasher::class],
            ['argon2id', Argon2IdHasher::class],
        ];
    }

    /**
     * Ensure that empty hashed values are treated as invalid.
     *
     * This prevents false positives when comparing against missing data.
     *
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $hasher
     * @return void
     */
    #[DataProvider('hasherProvider')]
    public function testEmptyHashedValueReturnsFalse(
        BcryptHasher|ArgonHasher|Argon2IdHasher $hasher
    ): void {
        $this->assertFalse(
            /*condition:*/ $hasher->check(/*value:*/ 'password', /*hashedValue:*/ ''),
            /*message:*/ 'Hasher should return false when given an empty hashed value.'
        );
    }

    /**
     * Ensure that null hashed values are treated as invalid.
     *
     * This protects against unexpected null inputs in authentication flows.
     *
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $hasher
     * @return void
     */
    #[DataProvider('hasherProvider')]
    public function testNullHashedValueReturnsFalse(
        BcryptHasher|ArgonHasher|Argon2IdHasher $hasher
    ): void {
        $this->assertFalse(
            /*condition:*/ $hasher->check(/*value:*/ 'password', /*hashedValue:*/ null),
            /*message:*/ 'Hasher should return false when given a null hashed value.'
        );
    }

    /**
     * Ensure bcrypt enforces its maximum input length (72 bytes).
     *
     * This is a known limitation of the algorithm and must be enforced
     * to avoid silent truncation vulnerabilities.
     *
     * @return void
     */
    public function testBcryptValueTooLong(): void
    {
        $this->expectException(exception: \InvalidArgumentException::class);

        (new BcryptHasher(/*options:*/ ['limit' => 72]))
            ->make(/*value:*/ str_repeat(string: 'a', times: 73));
    }

    /**
     * Validate core hashing lifecycle:
     * - Hash generation
     * - Verification
     * - Rehash detection
     * - Algorithm correctness
     * - Option enforcement
     *
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $hasher
     * @param  string  $expectedAlgo
     * @param  array<string, int>  $expectedOptions
     * @return void
     */
    #[DataProvider('basicHashingProvider')]
    public function testBasicHashing(
        BcryptHasher|ArgonHasher|Argon2IdHasher $hasher,
        string $expectedAlgo,
        array $expectedOptions
    ): void {
        $hashed = $hasher->make(/*value:*/ 'password');

        // Ensure the hashed value is different from the original input
        $this->assertNotSame(
            /*expected:*/ 'password',
            /*value:*/ $hashed,
            /*message:*/ 'Hashed value must differ from the original input.'
        );

        // Verify that the correct password validates successfully
        $this->assertTrue(
            /*condition:*/ $hasher->check(/*value:*/ 'password', /*hashedValue:*/ $hashed),
            /*message:*/ 'Hasher must validate the correct password.'
        );

        // Verify that an incorrect password fails validation
        $this->assertFalse(
            /*condition:*/ $hasher->needsRehash(/*hashedValue:*/ $hashed),
            /*message:*/ 'Fresh hashes should not require rehashing.'
        );

        // Simulate a configuration change that would require rehashing
        $rehashOptions = match ($expectedAlgo) {
            'bcrypt' => ['rounds' => PASSWORD_BCRYPT_DEFAULT_COST + 1],
            default => ['threads' => PASSWORD_ARGON2_DEFAULT_THREADS + 1],
        };

        // Ensure that the hasher detects when a rehash is needed due to configuration changes
        $this->assertTrue(
            /*condition:*/ $hasher->needsRehash(/*hashedValue:*/ $hashed, /*options:*/ $rehashOptions),
            /*message:*/ 'Hasher should detect when configuration changes require rehash.'
        );

        // Extract algorithm information from the hashed value to verify correct algorithm usage
        $info = password_get_info(hash: $hashed);

        // Verify that the expected algorithm was used in the hashing process
        $this->assertSame(
            /*expected:*/ $expectedAlgo,
            /*value:*/ $info['algoName'],
            /*message:*/ "Expected algorithm [{$expectedAlgo}] to be used."
        );

        // Verify that the hashing options meet or exceed the expected security parameters
        foreach ($expectedOptions as $option => $minValue) {
            $this->assertGreaterThanOrEqual(
                /*value:*/ $minValue,
                /*actual:*/ $info['options'][$option] ?? 0,
                /*message:*/ "Option [{$option}] should be at least {$minValue}."
            );
        }

        // Ensure that the HashManager recognizes the generated hash as valid
        $this->assertTrue(
            /*condition:*/ $this->hashManager->isHashed(/*value:*/ $hashed),
            /*message:*/ 'HashManager should recognize valid hashed values.'
        );

        // Ensure that the generated hash is not empty and is a string
        $this->assertNotEmpty(
            /*value:*/ $hashed,
            /*message:*/ 'Generated hash must not be empty.'
        );

        // Ensure that the generated hash is a string type
        $this->assertIsString(
            /*value:*/ $hashed,
            /*message:*/ 'Generated hash must be a string.'
        );
    }

    /**
     * Ensure that verifying a hash generated by a different algorithm fails.
     *
     * This enforces strict algorithm validation when "verify" mode is enabled.
     *
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $hasher
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $wrongHasher
     * @return void
     */
    #[DataProvider('crossAlgorithmProvider')]
    public function testVerificationWithWrongAlgorithm(
        BcryptHasher|ArgonHasher|Argon2IdHasher $hasher,
        BcryptHasher|ArgonHasher|Argon2IdHasher $wrongHasher
    ): void {
        $this->expectException(exception: RuntimeException::class);

        // Generate hash using a different algorithm
        $hashed = $wrongHasher->make(/*value:*/ 'password');

        // Attempt verification with mismatched hasher
        $hasher->check(
            /*value:*/ 'password',
            /*hashedValue:*/ $hashed
        );
    }

    /**
     * Ensure non-hash strings are not falsely detected as hashes.
     *
     * @return void
     */
    public function testIsHashedWithNonHashedValue(): void
    {
        $this->assertFalse(
            /*condition:*/ $this->hashManager->isHashed(/*value:*/ 'foo'),
            /*message:*/ 'Plain strings must not be considered hashed values.'
        );
    }

    /**
     * Ensure invalid hasher configurations throw exceptions.
     *
     * This protects against insecure or unsupported parameter usage.
     *
     * @param  BcryptHasher|ArgonHasher|Argon2IdHasher  $hasher
     * @return void
     */
    #[DataProvider('unsupportedConfigurationProvider')]
    public function testUnsupportedConfiguration(
        BcryptHasher|ArgonHasher|Argon2IdHasher $hasher
    ): void {
        $this->expectException(exception: RuntimeException::class);
        $hasher->make(/*value:*/ 'password');
    }

    /**
     * Ensure HashManager resolves the correct driver based on configuration.
     *
     * This verifies internal driver mapping consistency.
     *
     * @param  string  $driver
     * @param  class-string  $expected
     * @return void
     */
    #[DataProvider('hashManagerDriverProvider')]
    public function testHashManagerDriverSelection(
        string $driver,
        string $expected
    ): void {
        // Set the hashing driver via configuration
        $this->hashManager->getContainer()['config']->set('hashing.driver', $driver);

        // Resolve the driver instance from the HashManager
        $instance = $this->hashManager->driver();

        // Assert the resolved instance matches the expected hasher class
        $this->assertInstanceOf(/*expected:*/ $expected, /*actual:*/ $instance);
    }
}
