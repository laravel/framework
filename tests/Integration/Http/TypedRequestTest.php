<?php

namespace Illuminate\Tests\Integration\Http;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Attributes\HydrateFromRequest;
use Illuminate\Foundation\Http\Attributes\MapFrom;
use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Foundation\Http\Attributes\WithoutInferringRules;
use Illuminate\Foundation\Http\TypedFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Orchestra\Testbench\TestCase;

class TypedRequestTest extends TestCase
{
    public function testSimplifiedRequestDtoValidatesAndBuilds()
    {
        $request = Request::create('', parameters: ['number' => 42, 'string' => 'a']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedForm::class);

        $this->assertInstanceOf(MyTypedForm::class, $actual);
        $this->assertEquals(42, $actual->number);
        $this->assertEquals('a', $actual->string);
    }

    public function testSimplifiedRequestDtoFailsValidation()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['number' => 999, 'string' => 'z']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedForm::class);
    }

    public function testSimplifiedRequestDtoFailsAuthorization()
    {
        $this->expectException(AuthorizationException::class);

        $request = Request::create('', parameters: ['number' => 42, 'string' => 'a']);
        $this->app->instance('request', $request);

        $this->app->make(MyUnauthorizedTypedForm::class);
    }

    public function testDefaultPropertyUsedWhenKeyMissingFromRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertInstanceOf(MyTypedFormWithDefaults::class, $actual);
        $this->assertEquals('Taylor', $actual->name);
        $this->assertEquals(25, $actual->perPage);
    }

    public function testDefaultPropertyNotUsedWhenKeyPresentInRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'perPage' => 50]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertEquals(50, $actual->perPage);
    }

    public function testDefaultPropertyNotUsedWhenKeyPresentWithNullValue()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'perPage' => null]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertNull($actual->perPage);
    }

    public function testDefaultEnumPropertyUsedWhenKeyMissingFromRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertSame(SortDirection::Desc, $actual->sort);
    }

    public function testEnumPropertyOverriddenWhenKeyPresentInRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'sort' => 'asc']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertSame(SortDirection::Asc, $actual->sort);
    }

    public function testEnumPropertyFailsValidationWithInvalidValue()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'sort' => 'invalid']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormWithDefaults::class);
    }

    public function testAutoRulesFromTypesWithNoManualRules()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 30]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormAutoRules::class);

        $this->assertSame('Taylor', $actual->name);
        $this->assertSame(30, $actual->age);
        $this->assertNull($actual->bio);
        $this->assertSame(SortDirection::Asc, $actual->sort);
    }

    public function testAutoRulesRequiredFieldsMustBePresent()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        try {
            $this->app->make(MyTypedFormAutoRules::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayHasKey('age', $e->errors());
            $this->assertArrayNotHasKey('bio', $e->errors());
            $this->assertArrayNotHasKey('sort', $e->errors());
            $this->assertArrayNotHasKey('active', $e->errors());
        }
    }

    public function testAutoRulesRejectsWrongType()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 'not-a-number']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRules::class);
    }

    public function testAutoRulesOptionalFieldsCanBeOmitted()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 25]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormAutoRules::class);

        $this->assertNull($actual->bio);
        $this->assertSame(SortDirection::Asc, $actual->sort);
        $this->assertTrue($actual->active);
    }

    public function testManualRulesMergeWithAutoRules()
    {
        // min:1 comes from manual rules(), required+integer come from auto-rules
        // age=-5 should fail on min:1 (manual)
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => -5]);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRulesWithOverride::class);
    }

    public function testAutoRulesStillApplyWhenManualRulesOmitThem()
    {
        // Manual rules() only has min:1,max:120 for age — no 'required' or 'integer'
        // 'required' should still apply from auto-rules (age has no default)
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        try {
            $this->app->make(MyTypedFormAutoRulesWithOverride::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('age', $e->errors());
        }
    }

    public function testAutoRulesTypeCheckStillAppliesWhenManualRulesOmitIt()
    {
        // Manual rules() only has min:1,max:120 for age — no 'integer'
        // 'integer' should still apply from auto-rules
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 'not-a-number']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRulesWithOverride::class);
    }

    public function testNestedTypedFormRequestValidatesAndBuilds()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequest::class);

        $this->assertInstanceOf(CreateOrderRequest::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertInstanceOf(Address::class, $actual->address);
        $this->assertSame('123 Main St', $actual->address->street);
        $this->assertSame('Springfield', $actual->address->city);
        $this->assertNull($actual->address->zip);
    }

    public function testNestedTypedFormRequestFailsValidationOnMissingNestedField()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CreateOrderRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address.street', $e->errors());
        }
    }

    public function testNestedTypedFormRequestDefaultsApply()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                // zip omitted, should get default null
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequest::class);

        $this->assertNull($actual->address->zip);
    }

    public function testOptionalNestedTypedFormRequestOmittedEntirely()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            // address is omitted entirely
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequestWithOptionalAddress::class);

        $this->assertInstanceOf(CreateOrderRequestWithOptionalAddress::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertNull($actual->address);
    }

    public function testNestedTypedFormRequestRejectsScalarInputWithoutFatalError()
    {
        $request = Request::create('', parameters: [
            'address' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OptionalAddressWithoutInferredRulesRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address', $e->errors());
        }
    }

    public function testNestedTypedFormRequestWithMapFromRejectsScalarInputUsingMappedKey()
    {
        $request = Request::create('', parameters: [
            'shipping_address' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OptionalMappedAddressWithoutInferredRulesRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shipping_address', $e->errors());
            $this->assertArrayNotHasKey('address', $e->errors());
        }
    }

    public function testDeeplyNestedTypedFormRequestValidatesAndBuilds()
    {
        $request = Request::create('', parameters: [
            'street' => '456 New Ave',
            'city' => 'Shelbyville',
            'formerAddress' => [
                'street' => '123 Old St',
                'city' => 'Springfield',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(AddressWithAddressChild::class);

        $this->assertInstanceOf(AddressWithAddressChild::class, $actual);
        $this->assertSame('456 New Ave', $actual->street);
        $this->assertSame('Shelbyville', $actual->city);
        $this->assertInstanceOf(AddressWithAddressChild::class, $actual->formerAddress);
        $this->assertSame('123 Old St', $actual->formerAddress->street);
        $this->assertSame('Springfield', $actual->formerAddress->city);
        $this->assertNull($actual->formerAddress->zip);
        $this->assertNull($actual->formerAddress->formerAddress);
    }

    public function testDeeplyNestedTypedFormRequestTwoLevelsDeep()
    {
        $request = Request::create('', parameters: [
            'street' => '789 Current Rd',
            'city' => 'Capital City',
            'formerAddress' => [
                'street' => '456 New Ave',
                'city' => 'Shelbyville',
                'formerAddress' => [
                    'street' => '123 Old St',
                    'city' => 'Springfield',
                ],
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(AddressWithAddressChild::class);

        $this->assertInstanceOf(AddressWithAddressChild::class, $actual->formerAddress);
        $this->assertInstanceOf(AddressWithAddressChild::class, $actual->formerAddress->formerAddress);
        $this->assertSame('123 Old St', $actual->formerAddress->formerAddress->street);
        $this->assertSame('Springfield', $actual->formerAddress->formerAddress->city);
        $this->assertNull($actual->formerAddress->formerAddress->formerAddress);
    }

    public function testDeeplyNestedTypedFormRequestOmitted()
    {
        $request = Request::create('', parameters: [
            'street' => '456 New Ave',
            'city' => 'Shelbyville',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(AddressWithAddressChild::class);

        $this->assertSame('456 New Ave', $actual->street);
        $this->assertNull($actual->formerAddress);
    }

    public function testNestedMessagesArePrefixed()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                // street and city missing — should trigger custom messages
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OrderWithAddressMessages::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('address.street', $errors);
            $this->assertStringContainsString('We need your street', $errors['address.street'][0]);
        }
    }

    public function testNestedAttributesArePrefixed()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                // street and city missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OrderWithAddressMessages::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('address.city', $errors);
            $this->assertStringContainsString('city name', $errors['address.city'][0]);
        }
    }

    public function testDeeplyNestedTypedFormRequestFailsValidation()
    {
        $request = Request::create('', parameters: [
            'street' => '456 New Ave',
            'city' => 'Shelbyville',
            'formerAddress' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(AddressWithAddressChild::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('formerAddress.street', $e->errors());
        }
    }

    public function testMapFromMapsRequestFieldToParamName()
    {
        $request = Request::create('', parameters: [
            'first_name' => 'Taylor',
            'lastName' => 'Otwell',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MappedFieldRequest::class);

        $this->assertInstanceOf(MappedFieldRequest::class, $actual);
        $this->assertSame('Taylor', $actual->firstName);
        $this->assertSame('Otwell', $actual->lastName);
    }

    public function testMapFromValidationRulesUseMappedFieldName()
    {
        $request = Request::create('', parameters: [
            'lastName' => 'Otwell',
            // first_name is missing — should be required
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(MappedFieldRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('first_name', $e->errors());
            $this->assertArrayNotHasKey('firstName', $e->errors());
        }
    }

    public function testMapFromWithNestedTypedFormRequest()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(OrderWithMappedAddress::class);

        $this->assertInstanceOf(OrderWithMappedAddress::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertInstanceOf(Address::class, $actual->shippingAddress);
        $this->assertSame('123 Main St', $actual->shippingAddress->street);
        $this->assertSame('Springfield', $actual->shippingAddress->city);
    }

    public function testMapFromNestedValidationErrorUseMappedFieldName()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'shipping_address' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OrderWithMappedAddress::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shipping_address.street', $e->errors());
            $this->assertArrayNotHasKey('shippingAddress.street', $e->errors());
        }
    }

    public function testMapFromWithManualRulesUsesMappedFieldName()
    {
        $request = Request::create('', parameters: [
            'first_name' => 'T',
            'lastName' => 'Otwell',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(MappedFieldWithRulesRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            // Manual rule 'min:2' on 'first_name' should fail
            $this->assertArrayHasKey('first_name', $e->errors());
            $this->assertArrayNotHasKey('firstName', $e->errors());
        }
    }

    public function testMapFromWithManualRulesPassesValidation()
    {
        $request = Request::create('', parameters: [
            'first_name' => 'Taylor',
            'lastName' => 'Otwell',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MappedFieldWithRulesRequest::class);

        $this->assertInstanceOf(MappedFieldWithRulesRequest::class, $actual);
        $this->assertSame('Taylor', $actual->firstName);
        $this->assertSame('Otwell', $actual->lastName);
    }

    public function testMapFromDotNotationDefaultsApplyAndBuilds()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        $actual = $this->app->make(DotMappedDefaultRequest::class);

        $this->assertInstanceOf(DotMappedDefaultRequest::class, $actual);
        $this->assertSame(18, $actual->age);
    }

    public function testOptOutInferenceDoesNotAddStringRule()
    {
        $request = Request::create('', parameters: [
            'name' => 20, // numeric, should be allowed by manual rule only
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(OptOutInferenceRequest::class);

        $this->assertInstanceOf(OptOutInferenceRequest::class, $actual);
        $this->assertSame('20', $actual->name); // coerced by PHP scalar typing
    }

    public function testOptOutInferenceStillUsesManualRules()
    {
        $request = Request::create('', parameters: [
            'name' => 1, // min:2 should fail
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OptOutInferenceRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }


    public function testOptOutInferenceClassAttributeDisablesInferredRules()
    {
        $request = Request::create('', parameters: [
            'name' => 20, // numeric, should be allowed by manual rule only
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(OptOutInferenceOnClassRequest::class);

        $this->assertInstanceOf(OptOutInferenceOnClassRequest::class, $actual);
        $this->assertSame('20', $actual->name); // coerced by PHP scalar typing
    }

    public function testUnionBuiltinAcceptsInt()
    {
        $request = Request::create('', parameters: [
            'value' => 123,
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionBuiltinRequest::class);

        $this->assertInstanceOf(UnionBuiltinRequest::class, $actual);
        $this->assertSame(123, $actual->value);
    }

    public function testUnionBuiltinAcceptsString()
    {
        $request = Request::create('', parameters: [
            'value' => 'abc',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionBuiltinRequest::class);

        $this->assertInstanceOf(UnionBuiltinRequest::class, $actual);
        $this->assertSame('abc', $actual->value);
    }

    public function testUnionBuiltinRejectsArray()
    {
        $request = Request::create('', parameters: [
            'value' => ['invalid'],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionBuiltinRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('value', $e->errors());
        }
    }

    public function testUnionBuiltinMissingRequiredFieldFails()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionBuiltinRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('value', $e->errors());
        }
    }

    public function testUnionNullableAllowsNull()
    {
        $request = Request::create('', parameters: [
            'value' => null,
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionNullableRequest::class);

        $this->assertInstanceOf(UnionNullableRequest::class, $actual);
        $this->assertNull($actual->value);
    }

    public function testUnionNullableRejectsArray()
    {
        $request = Request::create('', parameters: [
            'value' => ['nope'],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionNullableRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('value', $e->errors());
        }
    }

    public function testUnionNestedAcceptsArrayAndBuildsDto()
    {
        $request = Request::create('', parameters: [
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionNestedRequest::class);

        $this->assertInstanceOf(UnionNestedRequest::class, $actual);
        $this->assertInstanceOf(Address::class, $actual->address);
        $this->assertSame('123 Main St', $actual->address->street);
    }

    public function testUnionNestedAcceptsString()
    {
        $request = Request::create('', parameters: [
            'address' => 'raw string',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionNestedRequest::class);

        $this->assertInstanceOf(UnionNestedRequest::class, $actual);
        $this->assertSame('raw string', $actual->address);
    }

    public function testUnionNestedRejectsInt()
    {
        $request = Request::create('', parameters: [
            'address' => 123,
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionNestedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address', $e->errors());
        }
    }

    public function testUnionNestedMissingFieldFailsValidation()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionNestedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address', $e->errors());
        }
    }

    public function testUnionNestedArrayBranchValidatesNestedFields()
    {
        $request = Request::create('', parameters: [
            'address' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionNestedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address.street', $e->errors());
        }
    }

    public function testUnionNestedMapFromArrayBranchUsesMappedValidationErrorKey()
    {
        $request = Request::create('', parameters: [
            'shipping_address' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(UnionNestedMappedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shipping_address.street', $e->errors());
            $this->assertArrayNotHasKey('shippingAddress.street', $e->errors());
        }
    }

    public function testUnionNestedMapFromStringBranchPasses()
    {
        $request = Request::create('', parameters: [
            'shipping_address' => 'raw string',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(UnionNestedMappedRequest::class);

        $this->assertInstanceOf(UnionNestedMappedRequest::class, $actual);
        $this->assertSame('raw string', $actual->shippingAddress);
    }

    public function testHydratableObjectBuildsFromArray()
    {
        $request = Request::create('', parameters: [
            'profile' => [
                'daysSinceILastPartied' => 10,
                'name' => 'Taylor',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(ProfileHydrationRequest::class);

        $this->assertInstanceOf(ProfileHydrationRequest::class, $actual);
        $this->assertInstanceOf(PartyProfile::class, $actual->profile);
        $this->assertSame(10, $actual->profile->daysSinceILastPartied);
        $this->assertSame('Taylor', $actual->profile->name);
        $this->assertTrue($actual->profile->wantsToParty);
    }

    public function testHydratableObjectMissingNestedRequiredFieldFailsValidation()
    {
        $request = Request::create('', parameters: [
            'profile' => [
                'name' => 'Taylor',
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ProfileHydrationRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('profile.daysSinceILastPartied', $e->errors());
        }
    }

    public function testHydratableObjectMapFromUsesMappedNestedErrorKey()
    {
        $request = Request::create('', parameters: [
            'profile_data' => [
                'name' => 'Taylor',
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ProfileHydrationMappedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('profile_data.daysSinceILastPartied', $e->errors());
            $this->assertArrayNotHasKey('profile.daysSinceILastPartied', $e->errors());
        }
    }

    public function testHydrateFromRequestClassAttributeAddsArrayValidationRule()
    {
        $request = Request::create('', parameters: [
            'profile' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ProfileHydrationRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('profile', $e->errors());
            $this->assertSame('not-an-array', $e->validator->getData()['profile']);
        }
    }

    public function testHydratableObjectPropertyOptInBuildsFromArray()
    {
        $request = Request::create('', parameters: [
            'profile' => [
                'daysSinceILastPartied' => 10,
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(ProfileHydrationPropertyOptInRequest::class);

        $this->assertInstanceOf(ProfileHydrationPropertyOptInRequest::class, $actual);
        $this->assertInstanceOf(PartyProfileWithoutClassOptIn::class, $actual->profile);
        $this->assertSame(10, $actual->profile->daysSinceILastPartied);
        $this->assertSame('Luke', $actual->profile->name);
    }

    public function testHydratableObjectPropertyOptInMissingNestedRequiredFieldFailsValidation()
    {
        $request = Request::create('', parameters: [
            'profile' => [
                'name' => 'Taylor',
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ProfileHydrationPropertyOptInRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('profile.daysSinceILastPartied', $e->errors());
        }
    }

    public function testHydratableObjectPropertyOptInMapFromUsesMappedNestedErrorKey()
    {
        $request = Request::create('', parameters: [
            'profile_data' => [
                'name' => 'Taylor',
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ProfileHydrationPropertyOptInMappedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('profile_data.daysSinceILastPartied', $e->errors());
            $this->assertArrayNotHasKey('profile.daysSinceILastPartied', $e->errors());
        }
    }

    public function testCarbonTypeAcceptsValidDateStringAndBuildsCarbon()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => '2025-01-15 13:45:00',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CarbonTypedRequest::class);

        $this->assertInstanceOf(CarbonTypedRequest::class, $actual);
        $this->assertInstanceOf(Carbon::class, $actual->publishedAt);
        $this->assertSame('2025-01-15 13:45:00', $actual->publishedAt->format('Y-m-d H:i:s'));
    }

    public function testCarbonTypeRejectsInvalidDateString()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => 'not-a-date',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CarbonTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('publishedAt', $e->errors());
        }
    }

    public function testCarbonTypeMissingRequiredFieldFailsValidation()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        try {
            $this->app->make(CarbonTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('publishedAt', $e->errors());
        }
    }

    public function testCarbonImmutableTypeAcceptsValidDateStringAndBuildsCarbonImmutable()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => '2025-01-15 13:45:00',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CarbonImmutableTypedRequest::class);

        $this->assertInstanceOf(CarbonImmutableTypedRequest::class, $actual);
        $this->assertInstanceOf(CarbonImmutable::class, $actual->publishedAt);
        $this->assertSame('2025-01-15 13:45:00', $actual->publishedAt->format('Y-m-d H:i:s'));
    }

    public function testCarbonMapFromMissingFieldUsesMappedValidationKey()
    {
        $request = Request::create('', parameters: []);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CarbonMappedTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('published_at', $e->errors());
            $this->assertArrayNotHasKey('publishedAt', $e->errors());
        }
    }

    public function testCarbonMapFromInvalidDateUsesMappedValidationKey()
    {
        $request = Request::create('', parameters: [
            'published_at' => 'not-a-date',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CarbonMappedTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('published_at', $e->errors());
            $this->assertArrayNotHasKey('publishedAt', $e->errors());
        }
    }

    public function testDateTimeInterfaceTypeAcceptsValidDateStringAndBuildsDateTimeInterface()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => '2025-02-10 09:30:00',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(DateTimeInterfaceTypedRequest::class);

        $this->assertInstanceOf(DateTimeInterfaceTypedRequest::class, $actual);
        $this->assertInstanceOf(DateTimeInterface::class, $actual->publishedAt);
        $this->assertSame('2025-02-10 09:30:00', $actual->publishedAt->format('Y-m-d H:i:s'));
    }

    public function testDateTimeInterfaceTypeRejectsInvalidDateString()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: [
            'publishedAt' => 'not-a-date',
        ]);
        $this->app->instance('request', $request);

        $this->app->make(DateTimeInterfaceTypedRequest::class);
    }

    public function testDateTimeTypeAcceptsValidDateStringAndBuildsDateTime()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => '2025-02-10 09:30:00',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(DateTimeTypedRequest::class);

        $this->assertInstanceOf(DateTimeTypedRequest::class, $actual);
        $this->assertInstanceOf(DateTime::class, $actual->publishedAt);
        $this->assertSame('2025-02-10 09:30:00', $actual->publishedAt->format('Y-m-d H:i:s'));
    }

    public function testDateTimeImmutableTypeAcceptsValidDateStringAndBuildsDateTimeImmutable()
    {
        $request = Request::create('', parameters: [
            'publishedAt' => '2025-02-10 09:30:00',
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(DateTimeImmutableTypedRequest::class);

        $this->assertInstanceOf(DateTimeImmutableTypedRequest::class, $actual);
        $this->assertInstanceOf(DateTimeImmutable::class, $actual->publishedAt);
        $this->assertSame('2025-02-10 09:30:00', $actual->publishedAt->format('Y-m-d H:i:s'));
    }

    public function testDateTimeInterfaceMapFromMissingFieldUsesMappedValidationKey()
    {
        $request = Request::create('', parameters: []);
        $this->app->instance('request', $request);

        try {
            $this->app->make(DateTimeInterfaceMappedTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('published_at', $e->errors());
            $this->assertArrayNotHasKey('publishedAt', $e->errors());
        }
    }

    public function testCollectionTypeAcceptsArrayAndBuildsCollection()
    {
        $request = Request::create('', parameters: [
            'items' => ['a', 'b'],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CollectionTypedRequest::class);

        $this->assertInstanceOf(Collection::class, $actual->items);
        $this->assertSame(['a', 'b'], $actual->items->all());
    }

    public function testNullableCollectionTypeAcceptsNull()
    {
        $request = Request::create('', parameters: [
            'items' => null,
        ]);

        $this->app->instance('request', $request);
        $actual = $this->app->make(CollectionTypedRequest::class);
        $this->assertNull($actual->items);
    }

    public function testCollectionTypeRejectsScalarInputWithValidationErrorKey()
    {
        $request = Request::create('', parameters: [
            'items' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CollectionTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }
    }

    public function testCollectionMapFromAcceptsArrayAndBuildsCollection()
    {
        $request = Request::create('', parameters: [
            'item_list' => [1, 2],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CollectionMappedTypedRequest::class);

        $this->assertInstanceOf(CollectionMappedTypedRequest::class, $actual);
        $this->assertInstanceOf(Collection::class, $actual->items);
        $this->assertSame([1, 2], $actual->items->all());
    }

    public function testCollectionMapFromRejectsScalarInputWithMappedValidationErrorKey()
    {
        $request = Request::create('', parameters: [
            'item_list' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CollectionMappedTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('item_list', $e->errors());
            $this->assertArrayNotHasKey('items', $e->errors());
        }
    }

    public function testCollectionTypeThrowsValidationExceptionWhenInferenceDisabled()
    {
        $request = Request::create('', parameters: [
            'items' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(OptionalCollectionWithoutInferredRulesRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
            $this->assertSame('The items field must be an array.', $e->errors()['items'][0]);
        }
    }

    public function testTrueTypeAcceptsTruthyValue()
    {
        $request = Request::create('', parameters: [
            'enabled' => true,
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(TrueTypedRequest::class);

        $this->assertInstanceOf(TrueTypedRequest::class, $actual);
        $this->assertTrue($actual->enabled);
    }

    public function testTrueTypeRejectsFalse()
    {
        $request = Request::create('', parameters: [
            'enabled' => false,
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(TrueTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('enabled', $e->errors());
        }
    }

    public function testFalseTypeAcceptsFalsyValue()
    {
        $request = Request::create('', parameters: [
            'disabled' => false,
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(FalseTypedRequest::class);

        $this->assertInstanceOf(FalseTypedRequest::class, $actual);
        $this->assertFalse($actual->disabled);
    }

    public function testFalseTypeRejectsTrue()
    {
        $request = Request::create('', parameters: [
            'disabled' => true,
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(FalseTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('disabled', $e->errors());
        }
    }

    public function testIterableTypeAcceptsArray()
    {
        $request = Request::create('', parameters: [
            'items' => ['a', 'b'],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(IterableTypedRequest::class);

        $this->assertInstanceOf(IterableTypedRequest::class, $actual);
        $this->assertSame(['a', 'b'], $actual->items);
    }

    public function testIterableTypeRejectsScalar()
    {
        $request = Request::create('', parameters: [
            'items' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(IterableTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }
    }

    public function testObjectTypeAcceptsArrayAndBuildsStdClass()
    {
        $request = Request::create('', parameters: [
            'someObject' => [
                'name' => 'Taylor',
                'days' => 3,
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(ObjectTypedRequest::class);

        $this->assertInstanceOf(ObjectTypedRequest::class, $actual);
        $this->assertInstanceOf(\stdClass::class, $actual->someObject);
        $this->assertSame('Taylor', $actual->someObject->name);
        $this->assertSame(3, $actual->someObject->days);
    }

    public function testObjectTypeRejectsScalarInputWithValidationErrorKey()
    {
        $request = Request::create('', parameters: [
            'someObject' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ObjectTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('someObject', $e->errors());
        }
    }

    public function testObjectMapFromAcceptsArrayAndBuildsStdClass()
    {
        $request = Request::create('', parameters: [
            'metadata' => [
                'name' => 'Taylor',
                'days' => 3,
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(ObjectMappedTypedRequest::class);

        $this->assertInstanceOf(ObjectMappedTypedRequest::class, $actual);
        $this->assertInstanceOf(\stdClass::class, $actual->someObject);
        $this->assertSame('Taylor', $actual->someObject->name);
        $this->assertSame(3, $actual->someObject->days);
    }

    public function testObjectMapFromRejectsScalarInputWithMappedValidationErrorKey()
    {
        $request = Request::create('', parameters: [
            'metadata' => 'not-an-array',
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(ObjectMappedTypedRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('metadata', $e->errors());
            $this->assertArrayNotHasKey('someObject', $e->errors());
        }
    }

    public function testWithValidatorHookIsCalledWhenDefined()
    {
        WithValidatorRequest::$withValidatorCalled = false;

        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $this->app->make(WithValidatorRequest::class);

        $this->assertTrue(WithValidatorRequest::$withValidatorCalled);
    }

    public function testWithValidatorHookCanAddConditionalRules()
    {
        $request = Request::create('', parameters: [
            'name' => 'Taylor',
            'requiresEmail' => true,
            // email is missing — withValidator adds 'required' for email when requiresEmail is true
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(WithValidatorConditionalRulesRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function testWithValidatorHookConditionalRulesNotAppliedWhenConditionFalse()
    {
        $request = Request::create('', parameters: [
            'name' => 'Taylor',
            'requiresEmail' => false,
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(WithValidatorConditionalRulesRequest::class);

        $this->assertInstanceOf(WithValidatorConditionalRulesRequest::class, $actual);
        $this->assertSame('Taylor', $actual->name);
    }

    public function testPassedValidationHookIsCalledOnSuccess()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $this->app->make(PassedValidationRequest::class);

        $this->assertTrue($request->attributes->get('passed_validation'));
    }

    public function testPassedValidationHookIsNotCalledOnFailure()
    {
        $request = Request::create('', parameters: []);
        $this->app->instance('request', $request);

        try {
            $this->app->make(PassedValidationRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertNull($request->attributes->get('passed_validation'));
        }
    }

    public function testFromBuildsFromArray()
    {
        $actual = MyTypedFormAutoRules::from(['name' => 'Taylor', 'age' => 30]);

        $this->assertInstanceOf(MyTypedFormAutoRules::class, $actual);
        $this->assertSame('Taylor', $actual->name);
        $this->assertSame(30, $actual->age);
    }

    public function testFromFailsValidation()
    {
        $this->expectException(ValidationException::class);

        MyTypedFormAutoRules::from(['name' => 'Taylor', 'age' => 'not-a-number']);
    }

    public function testFromSkipsAuthorization()
    {
        // MyUnauthorizedTypedForm::authorize() returns false,
        // but from() should skip authorization entirely.
        $actual = MyUnauthorizedTypedForm::from(['number' => 42, 'string' => 'hello']);

        $this->assertInstanceOf(MyUnauthorizedTypedForm::class, $actual);
        $this->assertSame(42, $actual->number);
        $this->assertSame('hello', $actual->string);
    }

    public function testFromBuildsWithDefaults()
    {
        $actual = MyTypedFormAutoRules::from(['name' => 'Taylor', 'age' => 25]);

        $this->assertNull($actual->bio);
        $this->assertSame(SortDirection::Asc, $actual->sort);
        $this->assertTrue($actual->active);
    }

    public function testFromBuildsNestedTypedFormRequest()
    {
        $actual = CreateOrderRequest::from([
            'item' => 'Widget',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ]);

        $this->assertInstanceOf(CreateOrderRequest::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertInstanceOf(Address::class, $actual->address);
        $this->assertSame('123 Main St', $actual->address->street);
    }

    public function testFromAcceptsRequestInstance()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 30]);

        $actual = MyTypedFormAutoRules::from($request);

        $this->assertInstanceOf(MyTypedFormAutoRules::class, $actual);
        $this->assertSame('Taylor', $actual->name);
        $this->assertSame(30, $actual->age);
    }

    public function testStopOnFirstFailureAttributeReportsOnlyOneError()
    {
        // Both name and age are required but neither is provided.
        $request = Request::create('', parameters: []);
        $this->app->instance('request', $request);

        try {
            $this->app->make(StopOnFirstFailureRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->errors());
            $this->assertArrayNotHasKey('age', $e->errors());
        }
    }
}

class MyTypedForm extends TypedFormRequest
{
    public function __construct(
        public int $number,
        public string $string,
    ) {
    }

    public static function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:100'],
            'string' => ['required', 'string', 'in:a,b,c'],
        ];
    }
}

class MyUnauthorizedTypedForm extends TypedFormRequest
{
    public function __construct(
        public int $number,
        public string $string,
    ) {
    }

    public static function rules(): array
    {
        return [
            'number' => ['required', 'integer'],
            'string' => ['required', 'string'],
        ];
    }

    public static function authorize(): bool
    {
        return false;
    }
}

class MyTypedFormWithDefaults extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public ?int $perPage = 25,
        public ?SortDirection $sort = SortDirection::Desc,
    ) {
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }
}

// No rules() method — all validation comes from constructor types
class MyTypedFormAutoRules extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $bio = null,
        public ?SortDirection $sort = SortDirection::Asc,
        public bool $active = true,
    ) {
    }
}

// Auto-rules as base, manual rules() adds constraints on top
class MyTypedFormAutoRulesWithOverride extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }

    // Only provides extra constraints — relies on auto-rules for 'required' and 'integer'
    public static function rules(): array
    {
        return [
            'age' => ['min:1', 'max:120'],
        ];
    }
}

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}

class Address extends TypedFormRequest
{
    public function __construct(
        public string $street,
        public string $city,
        public ?string $zip = null,
    ) {
    }
}

class CreateOrderRequest extends TypedFormRequest
{
    public function __construct(
        public string $item,
        public Address $address,
    ) {
    }
}

class CreateOrderRequestWithOptionalAddress extends TypedFormRequest
{
    public function __construct(
        public string $item,
        public ?Address $address = null,
    ) {
    }
}

class OptionalAddressWithoutInferredRulesRequest extends TypedFormRequest
{
    public function __construct(
        #[WithoutInferringRules]
        public ?Address $address = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'address' => ['nullable'],
        ];
    }
}

class OptionalMappedAddressWithoutInferredRulesRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('shipping_address')]
        #[WithoutInferringRules]
        public ?Address $address = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'shipping_address' => ['nullable'],
        ];
    }
}

class AddressWithMessages extends TypedFormRequest
{
    public function __construct(
        public string $street,
        public string $city,
    ) {
    }

    public static function messages(): array
    {
        return [
            'street.required' => 'We need your street address.',
        ];
    }

    public static function attributes(): array
    {
        return [
            'city' => 'city name',
        ];
    }
}

class OrderWithAddressMessages extends TypedFormRequest
{
    public function __construct(
        public string $item,
        public AddressWithMessages $address,
    ) {
    }
}

class AddressWithAddressChild extends TypedFormRequest
{
    public function __construct(
        public string $street,
        public string $city,
        public ?string $zip = null,
        public ?AddressWithAddressChild $formerAddress = null
    ) {
    }
}

class MappedFieldRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('first_name')]
        public string $firstName,
        public string $lastName,
    ) {
    }
}

class OrderWithMappedAddress extends TypedFormRequest
{
    public function __construct(
        public string $item,
        #[MapFrom('shipping_address')]
        public Address $shippingAddress,
    ) {
    }
}

class MappedFieldWithRulesRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('first_name')]
        public string $firstName,
        public string $lastName,
    ) {
    }

    public static function rules(): array
    {
        return [
            'first_name' => ['min:2', 'max:50'],
            'lastName' => ['min:2'],
        ];
    }
}

class DotMappedDefaultRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('meta.age')]
        public int $age = 18
    ) {
    }
}

class OptOutInferenceRequest extends TypedFormRequest
{
    public function __construct(
        #[WithoutInferringRules]
        public string $name,
    ) {
    }

    public static function rules(): array
    {
        return [
            'name' => ['min:2'],
        ];
    }
}

#[WithoutInferringRules]
class OptOutInferenceOnClassRequest extends TypedFormRequest
{
    public function __construct(
        public string $name,
    ) {
    }

    public static function rules(): array
    {
        return [
            'name' => ['min:2'],
        ];
    }
}

class UnionBuiltinRequest extends TypedFormRequest
{
    public function __construct(
        public int|string $value,
    ) {
    }
}

class UnionNullableRequest extends TypedFormRequest
{
    public function __construct(
        public int|string|null $value,
    ) {
    }
}

class UnionNestedRequest extends TypedFormRequest
{
    public function __construct(
        public Address|string $address,
    ) {
    }
}

class UnionNestedMappedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('shipping_address')]
        public Address|string $shippingAddress,
    ) {
    }
}

#[HydrateFromRequest]
class PartyProfile
{
    public function __construct(
        public int $daysSinceILastPartied,
        public string $name,
        public bool $wantsToParty = true,
    ) {
    }
}

class ProfileHydrationRequest extends TypedFormRequest
{
    public function __construct(
        public PartyProfile $profile,
    ) {
    }
}

class ProfileHydrationMappedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('profile_data')]
        public PartyProfile $profile,
    ) {
    }
}

class ProfileHydrationPropertyOptInRequest extends TypedFormRequest
{
    public function __construct(
        #[HydrateFromRequest]
        public PartyProfileWithoutClassOptIn $profile,
    ) {
    }
}

class ProfileHydrationPropertyOptInMappedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('profile_data')]
        #[HydrateFromRequest]
        public PartyProfileWithoutClassOptIn $profile,
    ) {
    }
}

class PartyProfileWithoutClassOptIn
{
    public function __construct(
        public int $daysSinceILastPartied,
        public string $name = 'Luke',
    ) {
    }
}

class CarbonTypedRequest extends TypedFormRequest
{
    public function __construct(
        public Carbon $publishedAt,
    ) {
    }
}

class CarbonImmutableTypedRequest extends TypedFormRequest
{
    public function __construct(
        public CarbonImmutable $publishedAt,
    ) {
    }
}

class CarbonMappedTypedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('published_at')]
        public Carbon $publishedAt,
    ) {
    }
}

class DateTimeInterfaceTypedRequest extends TypedFormRequest
{
    public function __construct(
        public DateTimeInterface $publishedAt,
    ) {
    }
}

class DateTimeInterfaceMappedTypedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('published_at')]
        public DateTimeInterface $publishedAt,
    ) {
    }
}

class DateTimeTypedRequest extends TypedFormRequest
{
    public function __construct(
        public DateTime $publishedAt,
    ) {
    }
}

class DateTimeImmutableTypedRequest extends TypedFormRequest
{
    public function __construct(
        public DateTimeImmutable $publishedAt,
    ) {
    }
}

class CollectionTypedRequest extends TypedFormRequest
{
    public function __construct(
        public ?Collection $items,
    ) {
    }
}

class CollectionMappedTypedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('item_list')]
        public Collection $items,
    ) {
    }
}

class OptionalCollectionWithoutInferredRulesRequest extends TypedFormRequest
{
    public function __construct(
        #[WithoutInferringRules]
        public ?Collection $items = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'items' => ['nullable'],
        ];
    }
}

class TrueTypedRequest extends TypedFormRequest
{
    public function __construct(
        public true $enabled,
    ) {
    }
}

class FalseTypedRequest extends TypedFormRequest
{
    public function __construct(
        public false $disabled,
    ) {
    }
}

class IterableTypedRequest extends TypedFormRequest
{
    public function __construct(
        public iterable $items,
    ) {
    }
}

class ObjectTypedRequest extends TypedFormRequest
{
    public function __construct(
        public object $someObject,
    ) {
    }
}

class ObjectMappedTypedRequest extends TypedFormRequest
{
    public function __construct(
        #[MapFrom('metadata')]
        public \stdClass $someObject,
    ) {
    }
}

class WithValidatorRequest extends TypedFormRequest
{
    public static bool $withValidatorCalled = false;

    public function __construct(
        public string $name,
    ) {
    }

    public static function withValidator(Validator $validator): void
    {
        static::$withValidatorCalled = true;
    }
}

class WithValidatorConditionalRulesRequest extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public bool $requiresEmail = false,
        #[WithoutInferringRules]
        public ?string $email = null,
    ) {
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->sometimes('email', 'required|email', function ($input) {
            return $input->requiresEmail;
        });
    }
}

class PassedValidationRequest extends TypedFormRequest
{
    public function __construct(
        public string $name,
    ) {
    }

    public static function passedValidation(Request $request): void
    {
        $request->attributes->add(['passed_validation' => true]);
    }
}

#[StopOnFirstFailure]
class StopOnFirstFailureRequest extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}
