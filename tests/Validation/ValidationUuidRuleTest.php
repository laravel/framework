<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Uuid;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\Uuid as UuidUuid;

class ValidationUuidRuleTest extends TestCase
{
    #[DataProvider('providesValidValidationDataAndRules')]
    public function testValidationPassesWhenPassingCorrectUuid(string $value, Uuid $rule)
    {
        $v = new Validator(
            resolve('translator'),
            [
                'uuid' => $value,
            ],
            [
                'uuid' => $rule,
            ]
        );

        $this->assertFalse($v->fails());
    }

    public static function providesValidValidationDataAndRules(): array
    {
        return [
            ['00000000-0000-0000-0000-000000000000', new Uuid],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', (new Uuid())->version(1)],
            ['85962c6a-98a5-11ef-8d2a-0242fe50b4b4', (new Uuid())->version(1)->node('02:42:fe:50:b4:b4')],
            ['000001f5-5e9a-21ea-9e00-0242ac130003', (new Uuid())->version(2)->domain(UuidUuid::DCE_DOMAIN_PERSON)->identifier(501)],
            ['85962c6a-98a5-11ef-8d2a-0242fe50b4b4', (new Uuid())->version(1)->dateTime(fn (Carbon $dateTime) => $dateTime->isSameDay('2024-11-01'))],
        ];
    }

    #[DataProvider('providesInvalidValidationDataAndRules')]
    public function testValidationPassesWhenPassingIncorrectUuid(string $value, Uuid $rule)
    {
        $v = new Validator(
            resolve('translator'),
            [
                'uuid' => $value,
            ],
            [
                'uuid' => $rule,
            ]
        );

        $this->assertTrue($v->fails());
    }

    public static function providesInvalidValidationDataAndRules(): array
    {
        return [
            ['ZZZZZZZZ-ZZZZ-ZZZZ-ZZZZ-ZZZZZZZZZZZZ', new Uuid],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', (new Uuid())->version(2)],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1', (new Uuid())->version(42)],
            ['85962c6a-98a5-11ef-8d2a-0242fe50b4b4', (new Uuid())->version(1)->node('ZZ:ZZ:ZZ:ZZ:ZZ:ZZ')],
            ['000001f5-5e9a-21ea-9e00-0242ac130003', (new Uuid())->version(2)->domain(UuidUuid::DCE_DOMAIN_GROUP)->identifier(501)],
            ['000001f5-5e9a-21ea-9e00-0242ac130003', (new Uuid())->version(2)->domain(UuidUuid::DCE_DOMAIN_PERSON)->identifier(42)],
            ['85962c6a-98a5-11ef-8d2a-0242fe50b4b4', (new Uuid())->version(1)->dateTime(fn (Carbon $dateTime) => $dateTime->isSameDay('2042-01-11'))],
        ];
    }

    #[DataProvider('providesWrongVersionsForConstraints')]
    public function testValidationThrowsPackageExceptionOnWrongVersion(string $value, Uuid $rule, string $exceptionClass, string $errorMessage)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($errorMessage);

        $v = new Validator(
            resolve('translator'),
            [
                'uuid' => $value,
            ],
            [
                'uuid' => $rule,
            ]
        );

        $v->passes();
    }

    /**
     * @return array<int, array<string, \Illuminate\Validation\Rules\Uuid, class-string, string>>
     */
    public static function providesWrongVersionsForConstraints(): array
    {
        return [
            [
                '85962c6a-98a5-11ef-8d2a-0242fe50b4b4',
                (new Uuid)->domain(UuidUuid::DCE_DOMAIN_GROUP)->identifier(501),
                InvalidArgumentException::class,
                'Fields used to create a UuidV2 must represent a version 2 (DCE Security) UUID',
            ],
            [
                '76a4ba72-cc4e-3e1d-b52d-856382f408c3',
                (new Uuid)->dateTime(fn (Carbon $dateTime) => $dateTime->isSameDay('2042-01-11')),
                UnsupportedOperationException::class,
                'Not a time-based UUID',
            ],
            [
                '76a4ba72-cc4e-3e1d-b52d-856382f408c3',
                (new Uuid)->node('ZZ:ZZ:ZZ:ZZ:ZZ:ZZ'),
                UnsupportedOperationException::class,
                'Not a node-based UUID',
            ],
        ];
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}
