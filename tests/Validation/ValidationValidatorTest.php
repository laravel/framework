<?php

namespace Illuminate\Tests\Validation;

use Countable;
use DateTime;
use DateTimeImmutable;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Stringable;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifierInterface;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationData;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidationValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
        m::close();
    }

    public function testNestedErrorMessagesAreRetrievedFromLocalArray()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'users' => [
                [
                    'name' => 'Taylor Otwell',
                    'posts' => [
                        [
                            'name' => '',
                        ],
                    ],
                ],
            ],
        ], [
            'users.*.name' => ['required'],
            'users.*.posts.*.name' => ['required'],
        ], [
            'users.*.name.required' => 'user name is required',
            'users.*.posts.*.name.required' => 'post name is required',
        ]);

        $this->assertFalse($v->passes());
        $this->assertSame('post name is required', $v->errors()->all()[0]);
    }

    public function testNestedArrayErrorMessagesAreRetrievedFromLocalArray()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'users' => [
                [
                    'name' => 'Taylor Otwell',
                    'posts' => [
                        [
                            'name' => '',
                        ],
                    ],
                ],
            ],
        ], [
            'users.*.name' => ['required'],
            'users.*.posts.*.name' => ['required'],
        ], [
            'users.*.name' => [
                'required' => 'user name is required',
            ],
            'users.*.posts.*.name' => [
                'required' => 'post name is required',
            ],
        ]);

        $this->assertFalse($v->passes());
        $this->assertSame('post name is required', $v->errors()->all()[0]);
    }

    public function testSometimesWorksOnNestedArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['bar' => ['baz' => '']]], ['foo.bar.baz' => 'sometimes|required']);
        $this->assertFalse($v->passes());
        $this->assertEquals(['foo.bar.baz' => ['Required' => []]], $v->failed());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['bar' => ['baz' => 'nonEmpty']]], ['foo.bar.baz' => 'sometimes|required']);
        $this->assertTrue($v->passes());
    }

    public function testAfterCallbacksAreCalledWithValidatorInstance()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Same:baz']);
        $v->setContainer(new Container);
        $v->after(function ($validator) {
            $_SERVER['__validator.after.test'] = true;

            // For asserting we can actually work with the instance
            $validator->errors()->add('bar', 'foo');
        });

        $this->assertFalse($v->passes());
        $this->assertTrue($_SERVER['__validator.after.test']);
        $this->assertTrue($v->errors()->has('bar'));

        unset($_SERVER['__validator.after.test']);
    }

    public function testSometimesWorksOnArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['bar', 'baz', 'moo']], ['foo' => 'sometimes|required|between:5,10']);
        $this->assertFalse($v->passes());
        $this->assertNotEmpty($v->failed());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['bar', 'baz', 'moo', 'pew', 'boom']], ['foo' => 'sometimes|required|between:5,10']);
        $this->assertTrue($v->passes());
    }

    public function testValidateThrowsOnFail()
    {
        $this->expectException(ValidationException::class);

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar'], ['baz' => 'required']);

        $v->validate();
    }

    public function testValidateDoesntThrowOnPass()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'required']);

        $this->assertSame(['foo' => 'bar'], $v->validate());
    }

    public function testHasFailedValidationRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Same:baz']);
        $this->assertFalse($v->passes());
        $this->assertEquals(['foo' => ['Same' => ['baz']]], $v->failed());
    }

    public function testFailingOnce()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Bail|Same:baz|In:qux']);
        $this->assertFalse($v->passes());
        $this->assertEquals(['foo' => ['Same' => ['baz']]], $v->failed());
    }

    public function testHasNotFailedValidationRules()
    {
        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->never();
        $v = new Validator($trans, ['foo' => 'taylor'], ['name' => 'Confirmed']);
        $this->assertTrue($v->passes());
        $this->assertEmpty($v->failed());
    }

    public function testSometimesCanSkipRequiredRules()
    {
        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->never();
        $v = new Validator($trans, [], ['name' => 'sometimes|required']);
        $this->assertTrue($v->passes());
        $this->assertEmpty($v->failed());
    }

    public function testInValidatableRulesReturnsValid()
    {
        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->never();
        $v = new Validator($trans, ['foo' => 'taylor'], ['name' => 'Confirmed']);
        $this->assertTrue($v->passes());
    }

    public function testValidateUsingNestedValidationRulesPasses()
    {
        $rules = [
            'items' => ['array'],
            'items.*' => ['array', ['required_array_keys', '|name']],
            'items.*.|name' => [['in', '|ABC123']],
        ];

        $data = [
            'items' => [
                ['|name' => '|ABC123'],
            ],
        ];

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, $data, $rules);

        $this->assertTrue($v->passes());

        $data = [
            'items' => [
                ['|name' => '|1234'],
            ],
        ];

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, $data, $rules);

        $this->assertSame('validation.in', $v->messages()->get('items.0.|name')[0]);
    }

    public function testValidateEmptyStringsAlwaysPasses()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => ''], ['x' => 'size:10|array|integer|min:5']);
        $this->assertTrue($v->passes());
    }

    public function testEmptyExistingAttributesAreValidated()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => ''], ['x' => 'array']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => []], ['x' => 'boolean']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => []], ['x' => 'numeric']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => []], ['x' => 'integer']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => []], ['x' => 'string']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, [], ['x' => 'string', 'y' => 'numeric', 'z' => 'integer', 'a' => 'boolean', 'b' => 'array']);
        $this->assertTrue($v->passes());
    }

    public function testNullable()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'x' => null, 'y' => null, 'z' => null, 'a' => null, 'b' => null,
        ], [
            'x' => 'string|nullable', 'y' => 'integer|nullable', 'z' => 'numeric|nullable', 'a' => 'array|nullable', 'b' => 'bool|nullable',
        ]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'x' => null, 'y' => null, 'z' => null, 'a' => null, 'b' => null,
        ], [
            'x' => 'string', 'y' => 'integer', 'z' => 'numeric', 'a' => 'array', 'b' => 'bool',
        ]);
        $this->assertTrue($v->fails());
        $this->assertSame('validation.string', $v->messages()->get('x')[0]);
        $this->assertSame('validation.integer', $v->messages()->get('y')[0]);
        $this->assertSame('validation.numeric', $v->messages()->get('z')[0]);
        $this->assertSame('validation.array', $v->messages()->get('a')[0]);
        $this->assertSame('validation.boolean', $v->messages()->get('b')[0]);
    }

    public function testArrayNullableWithUnvalidatedArrayKeys()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'x' => null,
        ], [
            'x' => 'array|nullable',
            'x.key' => 'string',
        ]);
        $this->assertTrue($v->passes());
        $this->assertArrayHasKey('x', $v->validated());

        $v = new Validator($trans, [
            'x' => null,
        ], [
            'x' => 'array',
            'x.key' => 'string',
        ]);
        $this->assertFalse($v->passes());
    }

    public function testNullableMakesNoDifferenceIfImplicitRuleExists()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'x' => null, 'y' => null,
        ], [
            'x' => 'nullable|required_with:y|integer',
            'y' => 'nullable|required_with:x|integer',
        ]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'x' => 'value', 'y' => null,
        ], [
            'x' => 'nullable|required_with:y|integer',
            'y' => 'nullable|required_with:x|integer',
        ]);
        $this->assertTrue($v->fails());
        $this->assertSame('validation.integer', $v->messages()->get('x')[0]);

        $v = new Validator($trans, [
            'x' => 123, 'y' => null,
        ], [
            'x' => 'nullable|required_with:y|integer',
            'y' => 'nullable|required_with:x|integer',
        ]);
        $this->assertTrue($v->fails());
        $this->assertSame('validation.required_with', $v->messages()->get('y')[0]);
    }

    public function testProperLanguageLineIsSet()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => 'required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');

        $this->assertSame('required!', $v->messages()->first('name'));
    }

    public function testCustomReplacersAreCalled()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => 'foo bar'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $v->addReplacer('required', function ($message, $attribute, $rule, $parameters) {
            return str_replace('bar', 'taylor', $message);
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo taylor', $v->messages()->first('name'));
    }

    public function testClassBasedCustomReplacers()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, [], ['name' => 'required']);
        $v->setContainer($container = m::mock(Container::class));
        $v->addReplacer('required', 'Foo@bar');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock(stdClass::class));
        $foo->shouldReceive('bar')->once()->andReturn('replaced!');
        $v->passes();
        $v->messages()->setFormat(':message');
        $this->assertSame('replaced!', $v->messages()->first('name'));
    }

    public function testNestedAttributesAreReplacedInDimensions()
    {
        // Knowing that demo image.png has width = 3 and height = 2
        $uploadedFile = new UploadedFile(__DIR__.'/fixtures/image.png', '', null, null, true);

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.dimensions' => ':min_width :max_height :ratio'], 'en');
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=10,max_height=20,ratio=1']);
        $v->messages()->setFormat(':message');
        $this->assertTrue($v->fails());
        $this->assertSame('10 20 1', $v->messages()->first('x'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.dimensions' => ':width :height :ratio'], 'en');
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=10,max_height=20,ratio=1']);
        $v->messages()->setFormat(':message');
        $this->assertTrue($v->fails());
        $this->assertSame(':width :height 1', $v->messages()->first('x'));
    }

    public function testAttributeNamesAreReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!', 'validation.attributes.name' => 'Name'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Name is required!', $v->messages()->first('name'));

        // set customAttributes by setter
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $customAttributes = ['name' => 'Name'];
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $v->addCustomAttributes($customAttributes);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $v->setAttributeNames(['name' => 'Name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':Attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':ATTRIBUTE is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('NAME is required!', $v->messages()->first('name'));
    }

    public function testAttributeNamesAreReplacedInArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['users' => [['country_code' => 'US'], ['country_code' => null]]], ['users.*.country_code' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('users.1.country_code is required!', $v->messages()->first('users.1.country_code'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.string' => ':attribute must be a string!',
            'validation.attributes.name.*' => 'Any name',
        ], 'en');
        $v = new Validator($trans, ['name' => ['Jon', 2]], ['name.*' => 'string']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Any name must be a string!', $v->messages()->first('name.1'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.string' => ':attribute must be a string!'], 'en');
        $v = new Validator($trans, ['name' => ['Jon', 2]], ['name.*' => 'string']);
        $v->setAttributeNames(['name.*' => 'Any name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Any name must be a string!', $v->messages()->first('name.1'));

        $v = new Validator($trans, ['users' => [['name' => 'Jon'], ['name' => 2]]], ['users.*.name' => 'string']);
        $v->setAttributeNames(['users.*.name' => 'Any name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Any name must be a string!', $v->messages()->first('users.1.name'));

        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['title' => ['nl' => '', 'en' => 'Hello']], ['title.*' => 'required'], [], ['title.nl' => 'Titel', 'title.en' => 'Title']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Titel is required!', $v->messages()->first('title.nl'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $trans->addLines(['validation.attributes' => ['names.*' => 'names']], 'en');
        $v = new Validator($trans, ['names' => [null, 'name']], ['names.*' => 'Required']);
        $v->messages()->setFormat(':message');
        $this->assertSame('names is required!', $v->messages()->first('names.0'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $trans->addLines(['validation.attributes' => ['names.*' => 'names']], 'en');
        $trans->addLines(['validation.attributes' => ['names.0' => 'First name']], 'en');
        $v = new Validator($trans, ['names' => [null, 'name']], ['names.*' => 'Required']);
        $v->messages()->setFormat(':message');
        $this->assertSame('First name is required!', $v->messages()->first('names.0'));
    }

    public function testInputIsReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.email' => ':input is not a valid email'], 'en');
        $v = new Validator($trans, ['email' => 'a@@s'], ['email' => 'email']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('a@@s is not a valid email', $v->messages()->first('email'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.email' => ':input is not a valid email'], 'en');
        $v = new Validator($trans, ['email' => null], ['email' => 'email']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('empty is not a valid email', $v->messages()->first('email'));
    }

    public function testInputIsReplacedByItsDisplayableValue()
    {
        $frameworks = [
            1 => 'Laravel',
            2 => 'Symfony',
            3 => 'Rails',
        ];

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.framework_php' => ':input is not a valid PHP Framework'], 'en');

        $v = new Validator($trans, ['framework' => 3], ['framework' => 'framework_php']);
        $v->addExtension('framework_php', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, [1, 2]);
        });
        $v->addCustomValues(['framework' => $frameworks]);

        $this->assertFalse($v->passes());
        $this->assertSame('Rails is not a valid PHP Framework', $v->messages()->first('framework'));
    }

    public function testDisplayableValuesAreReplaced()
    {
        // required_if:foo,bar
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $trans->addLines(['validation.values.color.1' => 'red'], 'en');
        $v = new Validator($trans, ['color' => '1', 'bar' => ''], ['bar' => 'RequiredIf:color,1']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The bar field is required when color is red.', $v->messages()->first('bar'));

        // required_if:foo,boolean
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $trans->addLines(['validation.values.subscribe.false' => 'false'], 'en');
        $v = new Validator($trans, ['subscribe' => false, 'bar' => ''], ['bar' => 'RequiredIf:subscribe,false']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The bar field is required when subscribe is false.', $v->messages()->first('bar'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $trans->addLines(['validation.values.subscribe.true' => 'true'], 'en');
        $v = new Validator($trans, ['subscribe' => true, 'bar' => ''], ['bar' => 'RequiredIf:subscribe,true']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The bar field is required when subscribe is true.', $v->messages()->first('bar'));

        // required_unless:foo,bar
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_unless' => 'The :attribute field is required unless :other is in :values.'], 'en');
        $trans->addLines(['validation.values.color.1' => 'red'], 'en');
        $v = new Validator($trans, ['color' => '2', 'bar' => ''], ['bar' => 'RequiredUnless:color,1']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The bar field is required unless color is in red.', $v->messages()->first('bar'));

        // in:foo,bar,...
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.in' => ':attribute must be included in :values.'], 'en');
        $trans->addLines(['validation.values.type.5' => 'Short'], 'en');
        $trans->addLines(['validation.values.type.300' => 'Long'], 'en');
        $v = new Validator($trans, ['type' => '4'], ['type' => 'in:5,300']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('type must be included in Short, Long.', $v->messages()->first('type'));

        // date_equals:tomorrow
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.date_equals' => 'The :attribute must be a date equal to :date.'], 'en');
        $trans->addLines(['validation.values.date.tomorrow' => 'the day after today'], 'en');
        $v = new Validator($trans, ['date' => date('Y-m-d')], ['date' => 'date_equals:tomorrow']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The date must be a date equal to the day after today.', $v->messages()->first('date'));

        // test addCustomValues
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.in' => ':attribute must be included in :values.'], 'en');
        $customValues = [
            'type' => [
                '5' => 'Short',
                '300' => 'Long',
            ],
        ];
        $v = new Validator($trans, ['type' => '4'], ['type' => 'in:5,300']);
        $v->addCustomValues($customValues);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('type must be included in Short, Long.', $v->messages()->first('type'));

        // set custom values by setter
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.in' => ':attribute must be included in :values.'], 'en');
        $customValues = [
            'type' => [
                '5' => 'Short',
                '300' => 'Long',
            ],
        ];
        $v = new Validator($trans, ['type' => '4'], ['type' => 'in:5,300']);
        $v->setValueNames($customValues);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('type must be included in Short, Long.', $v->messages()->first('type'));
    }

    public function testDisplayableAttributesAreReplacedInCustomReplacers()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        $trans->addLines(['validation.attributes.firstname' => 'Firstname'], 'en');
        $trans->addLines(['validation.attributes.lastname' => 'Lastname'], 'en');
        $v = new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
        $v->addExtension('alliteration', function ($attribute, $value, $parameters, $validator) {
            $other = Arr::get($validator->getData(), $parameters[0]);

            return $value[0] == $other[0];
        });
        $v->addReplacer('alliteration', function ($message, $attribute, $rule, $parameters, $validator) {
            return str_replace(':other', $validator->getDisplayableAttribute($parameters[0]), $message);
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Lastname needs to begin with the same letter as Firstname', $v->messages()->first('lastname'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        $customAttributes = ['firstname' => 'Firstname', 'lastname' => 'Lastname'];
        $v = new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
        $v->addCustomAttributes($customAttributes);
        $v->addExtension('alliteration', function ($attribute, $value, $parameters, $validator) {
            $other = Arr::get($validator->getData(), $parameters[0]);

            return $value[0] == $other[0];
        });
        $v->addReplacer('alliteration', function ($message, $attribute, $rule, $parameters, $validator) {
            return str_replace(':other', $validator->getDisplayableAttribute($parameters[0]), $message);
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('Lastname needs to begin with the same letter as Firstname', $v->messages()->first('lastname'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
    }

    public function testIndexValuesAreReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // $v = new Validator($trans, ['name' => ''], ['name' => 'required'], ['name.required' => 'Name :index is required.']);
        // $this->assertFalse($v->passes());
        // $this->assertSame('Name 0 is required.', $v->messages()->first('name'));

        $v = new Validator($trans, ['input' => [['name' => '']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :index is required.']);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 0 is required.', $v->messages()->first('input.*.name'));
        $v = new Validator($trans, ['input' => [['name' => '']]], ['input.*.name' => 'required'], ['input.*.name.required' => ':Attribute :index is required.']);
        $v->setAttributeNames([
            'input.*.name' => 'name',
        ]);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 0 is required.', $v->messages()->first('input.*.name'));

        $v = new Validator($trans, [
            'input' => [
                [
                    'name' => '',
                    'attributes' => [
                        'foo',
                        1,
                    ],
                ],
            ],
        ], ['input.*.attributes.*' => 'string'], ['input.*.attributes.*.string' => 'Attribute (:first-index, :first-position) (:second-index, :second-position) must be a string.']);
        $this->assertFalse($v->passes());
        $this->assertSame('Attribute (0, 1) (1, 2) must be a string.', $v->messages()->first('input.*.attributes.*'));

        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => ''], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :index is required.']);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 1 is required.', $v->messages()->first('input.*.name'));
        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => ''], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => ':Attribute :index is required.']);
        $v->setAttributeNames([
            'input.*.name' => 'name',
        ]);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 1 is required.', $v->messages()->first('input.*.name'));

        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :index is required.']);
        $this->assertTrue($v->passes());
    }

    public function testPositionValuesAreReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // $v = new Validator($trans, ['name' => ''], ['name' => 'required'], ['name.required' => 'Name :position is required.']);
        // $this->assertFalse($v->passes());
        // $this->assertSame('Name 1 is required.', $v->messages()->first('name'));

        $v = new Validator($trans, ['input' => [['name' => '']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :position is required.']);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 1 is required.', $v->messages()->first('input.*.name'));
        $v = new Validator($trans, ['input' => [['name' => '']]], ['input.*.name' => 'required'], ['input.*.name.required' => ':Attribute :position is required.']);
        $v->setAttributeNames([
            'input.*.name' => 'name',
        ]);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 1 is required.', $v->messages()->first('input.*.name'));

        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => ''], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :position is required.']);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 2 is required.', $v->messages()->first('input.*.name'));
        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => ''], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => ':Attribute :position is required.']);
        $v->setAttributeNames([
            'input.*.name' => 'name',
        ]);
        $this->assertFalse($v->passes());
        $this->assertSame('Name 2 is required.', $v->messages()->first('input.*.name'));

        $v = new Validator($trans, ['input' => [['name' => 'Bob'], ['name' => 'Jane']]], ['input.*.name' => 'required'], ['input.*.name.required' => 'Name :position is required.']);
        $this->assertTrue($v->passes());
    }

    public function testCustomValidationLinesAreRespected()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->getLoader()->addMessages('en', 'validation', [
            'required' => 'required!',
            'custom' => [
                'name' => [
                    'required' => 'really required!',
                ],
            ],
        ]);
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('really required!', $v->messages()->first('name'));
    }

    public function testCustomValidationLinesForSizeRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->getLoader()->addMessages('en', 'validation', [
            'required' => 'required!',
            'custom' => [
                'image' => [
                    'gte' => [
                        'file' => 'Custom message for image files.',
                        'string' => 'Custom message for image filenames.',
                    ],
                ],
            ],
        ]);

        $v = new Validator($trans, ['image' => 'image.png'], ['image' => 'gte:50']);
        $this->assertFalse($v->passes());
        $this->assertSame('Custom message for image filenames.', $v->messages()->first('image'));

        $file = new UploadedFile(__FILE__, '', null, null, true);
        $v = new Validator($trans, ['image' => $file], ['image' => 'gte:50']);
        $this->assertFalse($v->passes());
        $this->assertSame('Custom message for image files.', $v->messages()->first('image'));
    }

    public function testCustomValidationLinesAreRespectedWithAsterisks()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->getLoader()->addMessages('en', 'validation', [
            'required' => 'required!',
            'custom' => [
                'name.*' => [
                    'required' => 'all are really required!',
                ],
                'lang.en' => [
                    'required' => 'english is required!',
                ],
            ],
        ]);

        $v = new Validator($trans, ['name' => ['', ''], 'lang' => ['en' => '']], [
            'name.*' => 'required|max:255',
            'lang.*' => 'required|max:255',
        ]);

        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('all are really required!', $v->messages()->first('name.0'));
        $this->assertSame('all are really required!', $v->messages()->first('name.1'));
        $this->assertSame('english is required!', $v->messages()->first('lang.en'));
    }

    public function testCustomException()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['name' => ''], ['name' => 'required']);

        $exception = new class($v) extends ValidationException {};
        $v->setException($exception);

        try {
            $v->validate();
        } catch (ValidationException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testCustomExceptionMustExtendValidationException()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [], []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exception [RuntimeException] is invalid. It must extend [Illuminate\Validation\ValidationException].');

        $v->setException(RuntimeException::class);
    }

    public function testValidationDotCustomDotAnythingCanBeTranslated()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->getLoader()->addMessages('en', 'validation', [
            'required' => 'required!',
            'custom' => [
                'validation' => [
                    'custom.*' => [
                        'integer' => 'should be integer!',
                    ],
                ],
            ],
        ]);
        $v = new Validator($trans, ['validation' => ['custom' => ['string', 'string']]], ['validation.custom.*' => 'integer']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('should be integer!', $v->messages()->first('validation.custom.0'));
        $this->assertSame('should be integer!', $v->messages()->first('validation.custom.1'));
    }

    public function testInlineValidationMessagesAreRespected()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required'], ['name.required' => 'require it please!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('require it please!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required'], ['required' => 'require it please!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('require it please!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'foobarba'], ['name' => 'size:9'], ['size' => ['string' => ':attribute should be of length :size']]);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('name should be of length 9', $v->messages()->first('name'));
    }

    public function testInlineValidationMessagesAreRespectedWithAsterisks()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ['', '']], ['name.*' => 'required|max:255'], ['name.*.required' => 'all must be required!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('all must be required!', $v->messages()->first('name.0'));
        $this->assertSame('all must be required!', $v->messages()->first('name.1'));
    }

    public function testInlineValidationMessagesForRuleObjectsAreRespected()
    {
        $rule = new class implements Rule
        {
            public function passes($attribute, $value)
            {
                return false;
            }

            public function message()
            {
                return 'this is my message';
            }
        };

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'Taylor'], ['name' => $rule], [$rule::class => 'my custom message']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('my custom message', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'Ryan'], ['name' => $rule], ['name.'.$rule::class => 'my custom message']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('my custom message', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ['foo', 'bar']], ['name.*' => $rule], ['name.*.'.$rule::class => 'my custom message']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('my custom message', $v->messages()->first('name.0'));
        $this->assertSame('my custom message', $v->messages()->first('name.1'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'Ryan'], ['name' => $rule], [$rule::class => 'my attribute is :attribute']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('my attribute is name', $v->messages()->first('name'));
    }

    public function testIfRulesAreSuccessfullyAdded()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['foo' => 'Required']);
        // foo has required rule
        $this->assertTrue($v->hasRule('foo', 'Required'));
        // foo doesn't have array rule
        $this->assertFalse($v->hasRule('foo', 'Array'));
        // bar doesn't exists
        $this->assertFalse($v->hasRule('bar', 'Required'));
    }

    public function testValidateArray()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => new File('/tmp/foo', false)], ['foo' => 'Array']);
        $this->assertFalse($v->passes());
    }

    public function testValidateArrayKeys()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rules = ['user' => 'array:name,username'];

        $v = new Validator($trans, ['user' => ['name' => 'Duilio', 'username' => 'duilio']], $rules);
        $this->assertTrue($v->passes());

        // The array is valid if there's a missing key.
        $v = new Validator($trans, ['user' => ['name' => 'Duilio']], $rules);
        $this->assertTrue($v->passes());

        // But it's not valid if there's an unexpected key.
        $v = new Validator($trans, ['user' => ['name' => 'Duilio', 'username' => 'duilio', 'is_admin' => true]], $rules);
        $this->assertFalse($v->passes());
    }

    public function testValidateCurrentPassword()
    {
        // Fails when user is not logged in.
        $auth = m::mock(Guard::class);
        $auth->shouldReceive('guard')->andReturn($auth);
        $auth->shouldReceive('guest')->andReturn(true);

        $hasher = m::mock(Hasher::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('auth')->andReturn($auth);
        $container->shouldReceive('make')->with('hash')->andReturn($hasher);

        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->andReturnArg(0);

        $v = new Validator($trans, ['password' => 'foo'], ['password' => 'current_password']);
        $v->setContainer($container);

        $this->assertFalse($v->passes());

        // Fails when password is incorrect.
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword');

        $auth = m::mock(Guard::class);
        $auth->shouldReceive('guard')->andReturn($auth);
        $auth->shouldReceive('guest')->andReturn(false);
        $auth->shouldReceive('user')->andReturn($user);

        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->andReturn(false);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('auth')->andReturn($auth);
        $container->shouldReceive('make')->with('hash')->andReturn($hasher);

        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->andReturnArg(0);

        $v = new Validator($trans, ['password' => 'foo'], ['password' => 'current_password']);
        $v->setContainer($container);

        $this->assertFalse($v->passes());

        // Succeeds when password is correct.
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword');

        $auth = m::mock(Guard::class);
        $auth->shouldReceive('guard')->andReturn($auth);
        $auth->shouldReceive('guest')->andReturn(false);
        $auth->shouldReceive('user')->andReturn($user);

        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->andReturn(true);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('auth')->andReturn($auth);
        $container->shouldReceive('make')->with('hash')->andReturn($hasher);

        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->andReturnArg(0);

        $v = new Validator($trans, ['password' => 'foo'], ['password' => 'current_password']);
        $v->setContainer($container);

        $this->assertTrue($v->passes());

        // We can use a specific guard.
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthPassword');

        $auth = m::mock(Guard::class);
        $auth->shouldReceive('guard')->with('custom')->andReturn($auth);
        $auth->shouldReceive('guest')->andReturn(false);
        $auth->shouldReceive('user')->andReturn($user);

        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->andReturn(true);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('auth')->andReturn($auth);
        $container->shouldReceive('make')->with('hash')->andReturn($hasher);

        $trans = $this->getTranslator();
        $trans->shouldReceive('get')->andReturnArg(0);

        $v = new Validator($trans, ['password' => 'foo'], ['password' => 'current_password:custom']);
        $v->setContainer($container);

        $this->assertTrue($v->passes());
    }

    public function testValidateFilled()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['name' => 'filled']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => ''], ['name' => 'filled']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], []]], ['foo.*.id' => 'filled']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => '']]], ['foo.*.id' => 'filled']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => null]]], ['foo.*.id' => 'filled']);
        $this->assertFalse($v->passes());
    }

    public function testValidationStopsAtFailedPresenceCheck()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['name' => null], ['name' => 'Required|string']);
        $v->passes();
        $this->assertEquals(['validation.required'], $v->errors()->get('name'));

        $v = new Validator($trans, ['name' => null, 'email' => 'email'], ['name' => 'required_with:email|string']);
        $v->passes();
        $this->assertEquals(['validation.required_with'], $v->errors()->get('name'));

        $v = new Validator($trans, ['name' => null, 'email' => ''], ['name' => 'required_with:email|string']);
        $v->passes();
        $this->assertEquals(['validation.string'], $v->errors()->get('name'));

        $v = new Validator($trans, [], ['name' => 'present|string']);
        $v->passes();
        $this->assertEquals(['validation.present'], $v->errors()->get('name'));
    }

    public function testValidatePresent()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['name' => 'present']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['name' => 'present|nullable']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => null], ['name' => 'present|nullable']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => ''], ['name' => 'present']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], ['name' => 'a']]], ['foo.*.id' => 'present']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], []]], ['foo.*.id' => 'present']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], ['id' => '']]], ['foo.*.id' => 'present']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], ['id' => null]]], ['foo.*.id' => 'present']);
        $this->assertTrue($v->passes());
    }

    public function testValidatePresentIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_if' => 'The :attribute field must be present when :other is :value.'], 'en');

        $v = new Validator($trans, ['bar' => 1], ['foo' => 'present_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be present when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['bar' => 1, 'foo' => null], ['foo' => 'present_if:bar,2']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 1, 'foo' => ''], ['foo' => 'present_if:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 1, 'foo' => [['name' => 'a']]], ['foo.*.id' => 'present_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo.0.id field must be present when bar is 1.', $v->errors()->first('foo.0.id'));

        $v = new Validator($trans, ['bar' => 1, 'foo' => [['id' => '', 'name' => 'a']]], ['foo.*.id' => 'present_if:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 1, 'foo' => [['id' => null, 'name' => 'a']]], ['foo.*.id' => 'present_if:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 1, 'foo' => '2'], ['foo' => 'present_if:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2], ['foo' => 'present_if:bar,1']);
        $this->assertTrue($v->passes());
    }

    public function testValidatePresentUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_unless' => 'The :attribute field must be present unless :other is :value.'], 'en');

        $v = new Validator($trans, ['bar' => 2], ['foo' => 'present_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be present unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['bar' => 2, 'foo' => null], ['foo' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2, 'foo' => ''], ['foo' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2, 'foo' => [['name' => 'a']]], ['foo.*.id' => 'present_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo.0.id field must be present unless bar is 1.', $v->errors()->first('foo.0.id'));

        $v = new Validator($trans, ['bar' => 2, 'foo' => [['id' => '', 'name' => 'a']]], ['foo.*.id' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2, 'foo' => [['id' => null, 'name' => 'a']]], ['foo.*.id' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2, 'foo' => '2'], ['foo' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 1], ['foo' => 'present_unless:bar,1']);
        $this->assertTrue($v->passes());
    }

    public function testValidatePresentWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_with' => 'The :attribute field must be present when :values is present.'], 'en');

        $v = new Validator($trans, ['foo' => 1, 'bar' => 2], ['foo' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => null, 'bar' => 2], ['foo' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '', 'bar' => 2], ['foo' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['name' => 'a']], 'bar' => 2], ['foo.*.id' => 'present_with:bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo.0.id field must be present when bar is present.', $v->errors()->first('foo.0.id'));

        $v = new Validator($trans, ['foo' => [['id' => '']], 'bar' => 2], ['foo.*.id' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => null]], 'bar' => 2], ['foo.*.id' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1], ['foo' => 'present_with:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2], ['foo' => 'present_with:bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be present when bar is present.', $v->errors()->first('foo'));
    }

    public function testValidatePresentWithAll()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.present_with_all' => 'The :attribute field must be present when :values are present.'], 'en');

        $v = new Validator($trans, ['foo' => 1, 'bar' => 2, 'baz' => 1], ['foo' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => null, 'bar' => 2, 'baz' => 1], ['foo' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '', 'bar' => 2, 'baz' => 1], ['foo' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['name' => 'a']], 'bar' => 2, 'baz' => 1], ['foo.*.id' => 'present_with_all:bar,baz']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo.0.id field must be present when bar / baz are present.', $v->errors()->first('foo.0.id'));

        $v = new Validator($trans, ['foo' => [['id' => '']], 'bar' => 2, 'baz' => 1], ['foo.*.id' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => null]], 'bar' => 2, 'baz' => 1], ['foo.*.id' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1, 'bar' => 2], ['foo' => 'present_with_all:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['bar' => 2, 'baz' => 1], ['foo' => 'present_with_all:bar,baz']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be present when bar / baz are present.', $v->errors()->first('foo'));
    }

    public function testValidateRequired()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['name' => 'Required']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'Required']);
        $this->assertTrue($v->passes());

        $file = new File('', false);
        $v = new Validator($trans, ['name' => $file], ['name' => 'Required']);
        $this->assertFalse($v->passes());

        $file = new File(__FILE__, false);
        $v = new Validator($trans, ['name' => $file], ['name' => 'Required']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $file2 = new File(__FILE__, false);
        $v = new Validator($trans, ['files' => [$file, $file2]], ['files.0' => 'Required', 'files.1' => 'Required']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['files' => [$file, $file2]], ['files' => 'Required']);
        $this->assertTrue($v->passes());
    }

    public function testValidateRequiredWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'Taylor'], ['last' => 'required_with:first']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['first' => 'Taylor', 'last' => ''], ['last' => 'required_with:first']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['first' => ''], ['last' => 'required_with:first']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [], ['last' => 'required_with:first']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['first' => 'Taylor', 'last' => 'Otwell'], ['last' => 'required_with:first']);
        $this->assertTrue($v->passes());

        $file = new File('', false);
        $v = new Validator($trans, ['file' => $file, 'foo' => ''], ['foo' => 'required_with:file']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $foo = new File(__FILE__, false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_with:file']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $foo = new File('', false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_with:file']);
        $this->assertFalse($v->passes());
    }

    public function testRequiredWithAll()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'foo'], ['last' => 'required_with_all:first,foo']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['first' => 'foo'], ['last' => 'required_with_all:first']);
        $this->assertFalse($v->passes());
    }

    public function testValidateRequiredWithout()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'Taylor'], ['last' => 'required_without:first']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['first' => 'Taylor', 'last' => ''], ['last' => 'required_without:first']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['first' => ''], ['last' => 'required_without:first']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['last' => 'required_without:first']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['first' => 'Taylor', 'last' => 'Otwell'], ['last' => 'required_without:first']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['last' => 'Otwell'], ['last' => 'required_without:first']);
        $this->assertTrue($v->passes());

        $file = new File('', false);
        $v = new Validator($trans, ['file' => $file], ['foo' => 'required_without:file']);
        $this->assertFalse($v->passes());

        $foo = new File('', false);
        $v = new Validator($trans, ['foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertFalse($v->passes());

        $foo = new File(__FILE__, false);
        $v = new Validator($trans, ['foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $foo = new File(__FILE__, false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $foo = new File('', false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertTrue($v->passes());

        $file = new File('', false);
        $foo = new File(__FILE__, false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertTrue($v->passes());

        $file = new File('', false);
        $foo = new File('', false);
        $v = new Validator($trans, ['file' => $file, 'foo' => $foo], ['foo' => 'required_without:file']);
        $this->assertFalse($v->passes());
    }

    public function testRequiredWithoutMultiple()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $rules = [
            'f1' => 'required_without:f2,f3',
            'f2' => 'required_without:f1,f3',
            'f3' => 'required_without:f1,f2',
        ];

        $v = new Validator($trans, [], $rules);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['f1' => 'foo'], $rules);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['f2' => 'foo'], $rules);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['f3' => 'foo'], $rules);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['f1' => 'foo', 'f2' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f1' => 'foo', 'f3' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f2' => 'foo', 'f3' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f1' => 'foo', 'f2' => 'bar', 'f3' => 'baz'], $rules);
        $this->assertTrue($v->passes());
    }

    public function testRequiredWithoutAll()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $rules = [
            'f1' => 'required_without_all:f2,f3',
            'f2' => 'required_without_all:f1,f3',
            'f3' => 'required_without_all:f1,f2',
        ];

        $v = new Validator($trans, [], $rules);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['f1' => 'foo'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f2' => 'foo'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f3' => 'foo'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f1' => 'foo', 'f2' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f1' => 'foo', 'f3' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f2' => 'foo', 'f3' => 'bar'], $rules);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['f1' => 'foo', 'f2' => 'bar', 'f3' => 'baz'], $rules);
        $this->assertTrue($v->passes());
    }

    public function testRequiredIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor'], ['last' => 'required_if:first,taylor']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'required_if:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'required_if:first,taylor,dayle']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'dayle', 'last' => 'rees'], ['last' => 'required_if:first,taylor,dayle']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true], ['bar' => 'required_if:foo,false']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true], ['bar' => 'required_if:foo,null']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 0], ['bar' => 'required_if:foo,0']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '0'], ['bar' => 'required_if:foo,0']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 1], ['bar' => 'required_if:foo,1']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '1'], ['bar' => 'required_if:foo,1']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true], ['bar' => 'required_if:foo,true']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => false], ['bar' => 'required_if:foo,false']);
        $this->assertTrue($v->fails());

        // error message when passed multiple values (required_if:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $v = new Validator($trans, ['first' => 'dayle', 'last' => ''], ['last' => 'RequiredIf:first,taylor,dayle']);
        $this->assertFalse($v->passes());
        $this->assertSame('The last field is required when first is dayle.', $v->messages()->first('last'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 0], [
            'foo' => 'nullable|required|boolean',
            'bar' => 'required_if:foo,true',
            'baz' => 'required_if:foo,false',
        ]);
        $this->assertTrue($v->fails());
        $this->assertCount(1, $v->messages());
        $this->assertSame('The baz field is required when foo is 0.', $v->messages()->first('baz'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], [
            'foo' => 'nullable|boolean',
            'baz' => 'nullable|required_if:foo,false',
        ]);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => null], [
            'foo' => 'nullable|boolean',
            'baz' => 'nullable|required_if:foo,false',
        ]);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], [
            'foo' => 'nullable|boolean',
            'baz' => 'nullable|required_if:foo,null',
        ]);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => null], [
            'foo' => 'nullable|boolean',
            'baz' => 'nullable|required_if:foo,null',
        ]);
        $this->assertTrue($v->fails());
        $this->assertCount(1, $v->messages());
        $this->assertSame('The baz field is required when foo is empty.', $v->messages()->first('baz'));
    }

    public function testRequiredIfArrayToStringConversationErrorException()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'is_customer' => 1,
            'fullname' => null,
        ], [
            'is_customer' => 'required|boolean',
            'fullname' => 'required_if:is_customer,true',
        ]);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'is_customer' => ['test'],
            'fullname' => null,
        ], [
            'is_customer' => 'required|boolean',
            'fullname' => 'required_if:is_customer,true',
        ]);
        $this->assertTrue($v->fails());
    }

    public function testRequiredUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'sven'], ['last' => 'required_unless:first,taylor']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor'], ['last' => 'required_unless:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'sven', 'last' => 'wittevrongel'], ['last' => 'required_unless:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor'], ['last' => 'required_unless:first,taylor,sven']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'sven'], ['last' => 'required_unless:first,taylor,sven']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => false], ['bar' => 'required_unless:foo,false']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => false], ['bar' => 'required_unless:foo,true']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['bar' => '1'], ['bar' => 'required_unless:foo,true']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['bar' => 'required_unless:foo,true']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['bar' => 'required_unless:foo,null']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true], ['bar' => 'required_unless:foo,null']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '0'], ['bar' => 'required_unless:foo,0']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 0], ['bar' => 'required_unless:foo,0']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '1'], ['bar' => 'required_unless:foo,1']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 1], ['bar' => 'required_unless:foo,1']);
        $this->assertTrue($v->passes());

        // error message when passed multiple values (required_unless:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_unless' => 'The :attribute field is required unless :other is in :values.'], 'en');
        $v = new Validator($trans, ['first' => 'dayle', 'last' => ''], ['last' => 'RequiredUnless:first,taylor,sven']);
        $this->assertFalse($v->passes());
        $this->assertSame('The last field is required unless first is in taylor, sven.', $v->messages()->first('last'));
    }

    public function testProhibited()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [], ['name' => 'prohibited']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['last' => 'bar'], ['name' => 'prohibited']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'prohibited']);
        $this->assertTrue($v->fails());

        $file = new File('', false);
        $v = new Validator($trans, ['name' => $file], ['name' => 'prohibited']);
        $this->assertTrue($v->passes());

        $file = new File(__FILE__, false);
        $v = new Validator($trans, ['name' => $file], ['name' => 'prohibited']);
        $this->assertTrue($v->fails());

        $file = new File(__FILE__, false);
        $file2 = new File(__FILE__, false);
        $v = new Validator($trans, ['files' => [$file, $file2]], ['files.0' => 'prohibited', 'files.1' => 'prohibited']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['files' => [$file, $file2]], ['files' => 'prohibited']);
        $this->assertTrue($v->fails());
    }

    public function testProhibitedIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'prohibited_if:first,taylor']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor'], ['last' => 'prohibited_if:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'prohibited_if:first,taylor,jess']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor'], ['last' => 'prohibited_if:first,taylor,jess']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true, 'bar' => 'baz'], ['bar' => 'prohibited_if:foo,false']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => true, 'bar' => 'baz'], ['bar' => 'prohibited_if:foo,true']);
        $this->assertTrue($v->fails());

        // error message when passed multiple values (prohibited_if:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibited_if' => 'The :attribute field is prohibited when :other is :value.'], 'en');
        $v = new Validator($trans, ['first' => 'jess', 'last' => 'archer'], ['last' => 'prohibited_if:first,taylor,jess']);
        $this->assertFalse($v->passes());
        $this->assertSame('The last field is prohibited when first is jess.', $v->messages()->first('last'));
    }

    public function testProhibitedUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'jess', 'last' => 'archer'], ['last' => 'prohibited_unless:first,taylor']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'prohibited_unless:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'jess'], ['last' => 'prohibited_unless:first,taylor']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'taylor', 'last' => 'otwell'], ['last' => 'prohibited_unless:first,taylor,jess']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['first' => 'jess', 'last' => 'archer'], ['last' => 'prohibited_unless:first,taylor,jess']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => false, 'bar' => 'baz'], ['bar' => 'prohibited_unless:foo,false']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => false, 'bar' => 'baz'], ['bar' => 'prohibited_unless:foo,true']);
        $this->assertTrue($v->fails());

        // error message when passed multiple values (prohibited_unless:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.'], 'en');
        $v = new Validator($trans, ['first' => 'tim', 'last' => 'macdonald'], ['last' => 'prohibitedUnless:first,taylor,jess']);
        $this->assertFalse($v->passes());
        $this->assertSame('The last field is prohibited unless first is in taylor, jess.', $v->messages()->first('last'));
    }

    public function testProhibits()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => ['foo']], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => []], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => ''], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => null], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => false], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'emails' => ['foo']], ['email' => 'prohibits:email_address,emails']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'prohibits:emails']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo', 'other' => 'foo'], ['email' => 'prohibits:email_address,emails']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.prohibits' => 'The :attribute field prohibits :other being present.'], 'en');
        $v = new Validator($trans, ['email' => 'foo', 'emails' => 'bar', 'email_address' => 'baz'], ['email' => 'prohibits:emails,email_address']);
        $this->assertFalse($v->passes());
        $this->assertSame('The email field prohibits emails / email address being present.', $v->messages()->first('email'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'foo' => [
                ['email' => 'foo', 'emails' => 'foo'],
                ['emails' => 'foo'],
            ],
        ], ['foo.*.email' => 'prohibits:foo.*.emails']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.email'));
        $this->assertFalse($v->messages()->has('foo.1.email'));
    }

    /** @dataProvider prohibitedRulesData */
    public function testProhibitedRulesAreConsistent($rules, $data, $result)
    {
        $trans = $this->getIlluminateArrayTranslator();

        $this->assertSame($result, (new Validator($trans, $data, $rules))->passes());
    }

    public static function prohibitedRulesData()
    {
        $emptyCountable = new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        };

        return [
            // prohibited...
            [['p' => 'prohibited'], [], true],
            [['p' => 'prohibited'], ['p' => ''], true],
            [['p' => 'prohibited'], ['p' => ' '], true],
            [['p' => 'prohibited'], ['p' => null], true],
            [['p' => 'prohibited'], ['p' => []], true],
            [['p' => 'prohibited'], ['p' => $emptyCountable], true],
            [['p' => 'prohibited'], ['p' => 'foo'], false],

            // prohibited_if...
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => ''], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => ' '], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => null], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => []], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => $emptyCountable], true],
            [['p' => 'prohibited_if:bar,1'], ['bar' => 1, 'p' => 'foo'], false],

            // prohibitedIf...
            [['p' => new ProhibitedIf(true)], [], true],
            [['p' => new ProhibitedIf(true)], ['p' => ''], true],
            [['p' => new ProhibitedIf(true)], ['p' => ' '], true],
            [['p' => new ProhibitedIf(true)], ['p' => null], true],
            [['p' => new ProhibitedIf(true)], ['p' => []], true],
            [['p' => new ProhibitedIf(true)], ['p' => $emptyCountable], true],
            [['p' => new ProhibitedIf(true)], ['p' => 'foo'], false],

            // prohibited_unless...
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => ''], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => ' '], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => null], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => []], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => $emptyCountable], true],
            [['p' => 'prohibited_unless:bar,1'], ['bar' => 2, 'p' => 'foo'], false],

            // prohibits, with "p" values...
            [['p' => 'prohibits:bar'], [], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => ''], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => ' '], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => null], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => []], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => $emptyCountable], true],
            [['p' => 'prohibits:bar'], ['bar' => 2, 'p' => 'foo'], false],

            // prohibits, with "bar" values...
            [['p' => 'prohibits:bar'], ['p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => '', 'p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => ' ', 'p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => null, 'p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => [], 'p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => $emptyCountable, 'p' => 'foo'], true],
            [['p' => 'prohibits:bar'], ['bar' => 'foo', 'p' => 'foo'], false],
        ];
    }

    public function testFailedFileUploads()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // If file is not successfully uploaded validation should fail with a
        // 'uploaded' error message instead of the original rule.
        $file = m::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(false);
        $file->shouldNotReceive('getSize');
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.uploaded'], $v->errors()->get('photo'));

        // Even "required" will not run if the file failed to upload.
        $file = m::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->once()->andReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.uploaded'], $v->errors()->get('photo'));

        // It should only fail with that rule if a validation rule implies it's
        // a file. Otherwise it should fail with the regular rule.
        $file = m::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'string']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.string'], $v->errors()->get('photo'));

        // Validation shouldn't continue if a file failed to upload.
        $file = m::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->once()->andReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'file|mimes:pdf|min:10']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.uploaded'], $v->errors()->get('photo'));
    }

    public function testValidateInArray()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [1, 2, 3], 'bar' => [1, 2]], ['foo.*' => 'in_array:bar.*']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [1, 2], 'bar' => [1, 2, 3]], ['foo.*' => 'in_array:bar.*']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [['bar_id' => 5], ['bar_id' => 2]], 'bar' => [['id' => 1, ['id' => 2]]]], ['foo.*.bar_id' => 'in_array:bar.*.id']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [['bar_id' => 1], ['bar_id' => 2]], 'bar' => [['id' => 1, ['id' => 2]]]], ['foo.*.bar_id' => 'in_array:bar.*.id']);
        $this->assertTrue($v->passes());

        $trans->addLines(['validation.in_array' => 'The value of :attribute does not exist in :other.'], 'en');
        $v = new Validator($trans, ['foo' => [1, 2, 3], 'bar' => [1, 2]], ['foo.*' => 'in_array:bar.*']);
        $this->assertSame('The value of foo.2 does not exist in bar.*.', $v->messages()->first('foo.2'));
    }

    public function testValidateHexColor()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['color' => '#FFF'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#FFFF'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#FFFFFF'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#FF000080'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#FF000080'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#00FF0080'], ['color' => 'hex_color']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['color' => '#GGG'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#GGGG'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#123AB'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#GGGGGG'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#GGGGGGG'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#FFGG00FF'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
        $v = new Validator($trans, ['color' => '#00FF008X'], ['color' => 'hex_color']);
        $this->assertFalse($v->passes());
    }

    public function testValidateConfirmed()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['password' => 'foo'], ['password' => 'Confirmed']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['password' => 'foo', 'password_confirmation' => 'bar'], ['password' => 'Confirmed']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['password' => 'foo', 'password_confirmation' => 'foo'], ['password' => 'Confirmed']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['password' => '1e2', 'password_confirmation' => '100'], ['password' => 'Confirmed']);
        $this->assertFalse($v->passes());
    }

    public function testValidateSame()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Same:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'Same:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'bar'], ['foo' => 'Same:baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1e2', 'baz' => '100'], ['foo' => 'Same:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null, 'baz' => null], ['foo' => 'Same:baz']);
        $this->assertTrue($v->passes());
    }

    public function testValidateDifferent()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Different:baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'baz' => null], ['foo' => 'Different:baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'Different:baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'bar'], ['foo' => 'Different:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1e2', 'baz' => '100'], ['foo' => 'Different:baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'fuu' => 'baa', 'baz' => 'boom'], ['foo' => 'Different:fuu,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'Different:fuu,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'fuu' => 'bar', 'baz' => 'boom'], ['foo' => 'Different:fuu,baz']);
        $this->assertFalse($v->passes());
    }

    public function testGreaterThan()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 10], ['lhs' => 'numeric|gt:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 'string'], ['lhs' => 'numeric|gt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0, 'rhs' => 10], ['lhs' => 'numeric|gt:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => '15', 'rhs' => 10], ['lhs' => 'numeric|gt:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|gt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0], ['lhs' => 'numeric|gt:10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 5, 10 => 1], ['lhs' => 'numeric|gt:10']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => '15'], ['lhs' => 'numeric|gt:10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 'longer string', 'rhs' => 'string'], ['lhs' => 'gt:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => ['string'], 'rhs' => [1, 'string']], ['lhs' => 'gt:rhs']);
        $this->assertTrue($v->fails());

        $fileOne = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileOne->expects($this->any())->method('getSize')->willReturn(5472);
        $fileTwo = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileTwo->expects($this->any())->method('getSize')->willReturn(3151);
        $v = new Validator($trans, ['lhs' => $fileOne, 'rhs' => $fileTwo], ['lhs' => 'gt:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|gt:10']);
        $this->assertTrue($v->passes());
    }

    public function testLowercase()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'lower' => 'lowercase',
            'mixed' => 'MixedCase',
            'upper' => 'UPPERCASE',
            'lower_multibyte' => 'carácter multibyte',
            'mixed_multibyte' => 'carÁcter multibyte',
            'upper_multibyte' => 'CARÁCTER MULTIBYTE',
        ], [
            'lower' => 'lowercase',
            'mixed' => 'lowercase',
            'upper' => 'lowercase',
            'lower_multibyte' => 'lowercase',
            'mixed_multibyte' => 'lowercase',
            'upper_multibyte' => 'lowercase',
        ]);

        $this->assertSame([
            'mixed',
            'upper',
            'mixed_multibyte',
            'upper_multibyte',
        ], $v->messages()->keys());
    }

    public function testUppercase()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'lower' => 'lowercase',
            'mixed' => 'MixedCase',
            'upper' => 'UPPERCASE',
            'lower_multibyte' => 'carácter multibyte',
            'mixed_multibyte' => 'carÁcter multibyte',
            'upper_multibyte' => 'CARÁCTER MULTIBYTE',
        ], [
            'lower' => 'uppercase',
            'mixed' => 'uppercase',
            'upper' => 'uppercase',
            'lower_multibyte' => 'uppercase',
            'mixed_multibyte' => 'uppercase',
            'upper_multibyte' => 'uppercase',
        ]);

        $this->assertSame([
            'lower',
            'mixed',
            'lower_multibyte',
            'mixed_multibyte',
        ], $v->messages()->keys());
    }

    public function testLessThan()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 10], ['lhs' => 'numeric|lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 'string'], ['lhs' => 'numeric|lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0, 'rhs' => 10], ['lhs' => 'numeric|lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => '15', 'rhs' => 10], ['lhs' => 'numeric|lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0], ['lhs' => 'numeric|lt:10']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => '15'], ['lhs' => 'numeric|lt:10']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 'longer string', 'rhs' => 'string'], ['lhs' => 'lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => ['string'], 'rhs' => [1, 'string']], ['lhs' => 'lt:rhs']);
        $this->assertTrue($v->passes());

        $fileOne = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileOne->expects($this->any())->method('getSize')->willReturn(5472);
        $fileTwo = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileTwo->expects($this->any())->method('getSize')->willReturn(3151);
        $v = new Validator($trans, ['lhs' => $fileOne, 'rhs' => $fileTwo], ['lhs' => 'lt:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|lt:10']);
        $this->assertTrue($v->fails());
    }

    public function testGreaterThanOrEqual()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 15], ['lhs' => 'numeric|gte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 'string'], ['lhs' => 'numeric|gte:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0, 'rhs' => 15], ['lhs' => 'numeric|gte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => '15', 'rhs' => 15], ['lhs' => 'numeric|gte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|gte:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0], ['lhs' => 'numeric|gte:15']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => '15'], ['lhs' => 'numeric|gte:15']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 'longer string', 'rhs' => 'string'], ['lhs' => 'gte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => ['string'], 'rhs' => [1, 'string']], ['lhs' => 'gte:rhs']);
        $this->assertTrue($v->fails());

        $fileOne = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileOne->expects($this->any())->method('getSize')->willReturn(5472);
        $fileTwo = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileTwo->expects($this->any())->method('getSize')->willReturn(5472);
        $v = new Validator($trans, ['lhs' => $fileOne, 'rhs' => $fileTwo], ['lhs' => 'gte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|gte:15']);
        $this->assertTrue($v->passes());
    }

    public function testLessThanOrEqual()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 15], ['lhs' => 'numeric|lte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15, 'rhs' => 'string'], ['lhs' => 'numeric|lte:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0, 'rhs' => 15], ['lhs' => 'numeric|lte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => '15', 'rhs' => 15], ['lhs' => 'numeric|lte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|lte:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 15.0], ['lhs' => 'numeric|lte:10']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => '15'], ['lhs' => 'numeric|lte:10']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => 'longer string', 'rhs' => 'string'], ['lhs' => 'lte:rhs']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['lhs' => ['string'], 'rhs' => [1, 'string']], ['lhs' => 'lte:rhs']);
        $this->assertTrue($v->passes());

        $fileOne = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileOne->expects($this->any())->method('getSize')->willReturn(5472);
        $fileTwo = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $fileTwo->expects($this->any())->method('getSize')->willReturn(5472);
        $v = new Validator($trans, ['lhs' => $fileOne, 'rhs' => $fileTwo], ['lhs' => 'lte:rhs']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['lhs' => 15], ['lhs' => 'numeric|lte:10']);
        $this->assertTrue($v->fails());
    }

    public function testValidateAccepted()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no'], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'off'], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 0], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '0'], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => false], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'false'], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'yes'], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'on'], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => true], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'true'], ['foo' => 'Accepted']);
        $this->assertTrue($v->passes());
    }

    public function testValidateRequiredAcceptedIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no', 'bar' => 'baz'], ['bar' => 'required_if_accepted:foo']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => 'baz'], ['bar' => 'required_if_accepted:foo']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'no', 'bar' => ''], ['bar' => 'required_if_accepted:foo']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => ''], ['bar' => 'required_if_accepted:foo']);
        $this->assertFalse($v->passes());
    }

    public function testValidateAcceptedIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'off', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null, 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 0, 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '0', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => false, 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'false', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'on', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1, 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => true, 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'true', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertTrue($v->passes());

        // accepted_if:bar,aaa
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'no', 'bar' => 'aaa'], ['foo' => 'accepted_if:bar,aaa']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be accepted when bar is aaa.', $v->messages()->first('foo'));

        // accepted_if:bar,aaa,...
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'no', 'bar' => 'abc'], ['foo' => 'accepted_if:bar,aaa,bbb,abc']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be accepted when bar is abc.', $v->messages()->first('foo'));

        // accepted_if:bar,boolean
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'no', 'bar' => false], ['foo' => 'accepted_if:bar,false']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be accepted when bar is false.', $v->messages()->first('foo'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.accepted_if' => 'The :attribute field must be accepted when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'no', 'bar' => true], ['foo' => 'accepted_if:bar,true']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be accepted when bar is true.', $v->messages()->first('foo'));
    }

    public function testValidateDeclined()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'yes'], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'on'], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 1], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => true], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'true'], ['foo' => 'Declined']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'no'], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'off'], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0'], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 0], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => false], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'false'], ['foo' => 'Declined']);
        $this->assertTrue($v->passes());
    }

    public function testValidateMissing()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing' => 'The :attribute field must be missing.'], 'en');

        $v = new Validator($trans, ['foo' => 'yes'], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ''], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ' '], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => null], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => []], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        }, ], ['foo' => 'missing']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['bar' => 'bar'], ['foo' => 'missing']);
        $this->assertTrue($v->passes());
    }

    public function testValidateMissingIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_if' => 'The :attribute field must be missing when :other is :value.'], 'en');

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => '1'], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => '', 'bar' => '1'], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ' ', 'bar' => '1'], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => null, 'bar' => '1'], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => [], 'bar' => '1'], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        }, 'bar' => '1', ], ['foo' => 'missing_if:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => 'foo', 'bar' => '2'], ['foo' => 'missing_if:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [0 => ['bar' => 1, 'baz' => 'should be missing']]], ['foo.*.baz' => 'missing_if:foo.*.bar,1']);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo.0.baz field must be missing when foo.0.bar is 1.', $v->errors()->first('foo.0.baz'));
    }

    public function testValidateMissingUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_unless' => 'The :attribute field must be missing unless :other is :value.'], 'en');

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => '2'], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => '', 'bar' => '2'], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ' ', 'bar' => '2'], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => null, 'bar' => '2'], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => [], 'bar' => '2'], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        }, 'bar' => '2', ], ['foo' => 'missing_unless:bar,1']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing unless bar is 1.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => 'foo', 'bar' => '1'], ['foo' => 'missing_unless:bar,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [0 => ['bar' => 0, 'baz' => 'should be missing']]], ['foo.*.baz' => 'missing_unless:foo.*.bar,1']);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo.0.baz field must be missing unless foo.0.bar is 1.', $v->errors()->first('foo.0.baz'));
    }

    public function testValidateMissingWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_with' => 'The :attribute field must be missing when :values is present.'], 'en');

        $v = new Validator($trans, ['bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => '', 'bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ' ', 'bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => null, 'bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => [], 'bar' => '2'], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        }, 'bar' => '2', ], ['foo' => 'missing_with:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar is present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => 'foo', 'qux' => '1'], ['foo' => 'missing_with:baz,bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [0 => ['bar' => 1, 'baz' => 'should be missing']]], ['foo.*.baz' => 'missing_with:foo.*.bar,foo.*.fred']);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo.0.baz field must be missing when foo.0.bar / foo.0.fred is present.', $v->errors()->first('foo.0.baz'));
    }

    public function testValidateMissingWithAll()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.missing_with_all' => 'The :attribute field must be missing when :values are present.'], 'en');

        $v = new Validator($trans, ['bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'yes', 'bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => '', 'bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => ' ', 'bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => null, 'bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => [], 'bar' => '2', 'baz' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => new class implements Countable
        {
            public function count(): int
            {
                return 0;
            }
        }, 'bar' => '2', 'baz' => '2', ], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertFalse($v->passes());
        $this->assertSame('The foo field must be missing when baz / bar are present.', $v->errors()->first('foo'));

        $v = new Validator($trans, ['foo' => [], 'bar' => '2', 'qux' => '2'], ['foo' => 'missing_with_all:baz,bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [0 => ['bar' => 1, 'fred' => 2, 'baz' => 'should be missing']]], ['foo.*.baz' => 'missing_with_all:foo.*.bar,foo.*.fred']);
        $this->assertTrue($v->fails());
        $this->assertSame('The foo.0.baz field must be missing when foo.0.bar / foo.0.fred are present.', $v->errors()->first('foo.0.baz'));
    }

    public function testValidateDeclinedIf()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'yes', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'on', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null, 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 1, 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => true, 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'true', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'no', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'off', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 0, 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => false, 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'false', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertTrue($v->passes());

        // declined_if:bar,aaa
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'yes', 'bar' => 'aaa'], ['foo' => 'declined_if:bar,aaa']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be declined when bar is aaa.', $v->messages()->first('foo'));

        // declined_if:bar,aaa,...
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'yes', 'bar' => 'abc'], ['foo' => 'declined_if:bar,aaa,bbb,abc']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be declined when bar is abc.', $v->messages()->first('foo'));

        // declined_if:bar,boolean
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'yes', 'bar' => false], ['foo' => 'declined_if:bar,false']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be declined when bar is false.', $v->messages()->first('foo'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.declined_if' => 'The :attribute field must be declined when :other is :value.'], 'en');
        $v = new Validator($trans, ['foo' => 'yes', 'bar' => true], ['foo' => 'declined_if:bar,true']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('The foo field must be declined when bar is true.', $v->messages()->first('foo'));
    }

    public function testValidateEndsWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'ends_with:hello']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'ends_with:world']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'ends_with:world,hello']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.ends_with' => 'The :attribute must end with one of the following values :values'], 'en');
        $v = new Validator($trans, ['url' => 'laravel.com'], ['url' => 'ends_with:http']);
        $this->assertFalse($v->passes());
        $this->assertSame('The url must end with one of the following values http', $v->messages()->first('url'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.ends_with' => 'The :attribute must end with one of the following values :values'], 'en');
        $v = new Validator($trans, ['url' => 'laravel.com'], ['url' => 'ends_with:http,https']);
        $this->assertFalse($v->passes());
        $this->assertSame('The url must end with one of the following values http, https', $v->messages()->first('url'));
    }

    public function testValidateDoesntEndWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'doesnt_end_with:hello']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'doesnt_end_with:world']);
        $this->assertFalse($v->passes());
    }

    public function testValidateStartsWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'starts_with:hello']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'starts_with:world']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'starts_with:world,hello']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.starts_with' => 'The :attribute must start with one of the following values :values'], 'en');
        $v = new Validator($trans, ['url' => 'laravel.com'], ['url' => 'starts_with:http']);
        $this->assertFalse($v->passes());
        $this->assertSame('The url must start with one of the following values http', $v->messages()->first('url'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.starts_with' => 'The :attribute must start with one of the following values :values'], 'en');
        $v = new Validator($trans, ['url' => 'laravel.com'], ['url' => 'starts_with:http,https']);
        $this->assertFalse($v->passes());
        $this->assertSame('The url must start with one of the following values http, https', $v->messages()->first('url'));
    }

    public function testValidateDoesntStartWith()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'world hello'], ['x' => 'doesnt_start_with:hello']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'hello world'], ['x' => 'doesnt_start_with:hello']);
        $this->assertFalse($v->passes());
    }

    public function testValidateString()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'string']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => ['blah' => 'test']], ['x' => 'string']);
        $this->assertFalse($v->passes());
    }

    public function testValidateJson()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'aslksd'], ['foo' => 'json']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '[]'], ['foo' => 'json']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '{"name":"John","age":"34"}'], ['foo' => 'json']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['array']], ['foo' => 'json']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => null], ['foo' => 'json']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => new Stringable('[]')], ['foo' => 'json']);
        $this->assertTrue($v->passes());
    }

    public function testValidateBoolean()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no'], ['foo' => 'Boolean']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'yes'], ['foo' => 'Boolean']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'false'], ['foo' => 'Boolean']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'true'], ['foo' => 'Boolean']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => false], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => true], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0'], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 0], ['foo' => 'Boolean']);
        $this->assertTrue($v->passes());
    }

    public function testValidateBool()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no'], ['foo' => 'Bool']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'yes'], ['foo' => 'Bool']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'false'], ['foo' => 'Bool']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'true'], ['foo' => 'Bool']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => false], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => true], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 1], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0'], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 0], ['foo' => 'Bool']);
        $this->assertTrue($v->passes());
    }

    public function testValidateNumeric()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Numeric']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Numeric']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '-1'], ['foo' => 'Numeric']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Numeric']);
        $this->assertTrue($v->passes());
    }

    public function testValidateInteger()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Integer']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Integer']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '-1'], ['foo' => 'Integer']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Integer']);
        $this->assertTrue($v->passes());
    }

    public function testValidateDecimal()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Decimal:2,3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.2345'], ['foo' => 'Decimal:2,3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.234'], ['foo' => 'Decimal:2,3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '-1.234'], ['foo' => 'Decimal:2,3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Decimal:2,3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '+1.23'], ['foo' => 'Decimal:2,3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.2'], ['foo' => 'Decimal:2,3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Decimal:2']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '-1.23'], ['foo' => 'Decimal:2']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.233'], ['foo' => 'Decimal:2']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.2'], ['foo' => 'Decimal:2']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Decimal:0,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.2'], ['foo' => 'Decimal:0,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '-1.2'], ['foo' => 'Decimal:0,1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Decimal:0,1']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.8888888888'], ['foo' => 'Decimal:10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Min:1.88888888888888888889']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Min:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Max:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Max:1.88888888888888888887']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Max:1.88888888888888888887']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Size:1.88888888888888888889']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Size:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889'], ['foo' => 'Decimal:20|Between:1.88888888888888888886,1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888887'], ['foo' => 'Decimal:20|Between:1.88888888888888888886,1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gt:1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889'], ['foo' => 'Decimal:20|Gt:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gt:bar']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gt:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lt:1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888887'], ['foo' => 'Decimal:20|Lt:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lt:bar']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888887', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lt:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888887'], ['foo' => 'Decimal:20|Gte:1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gte:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888887', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gte:bar']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Gte:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889'], ['foo' => 'Decimal:20|Lte:1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lte:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lte:bar']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888', 'bar' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Lte:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888889'], ['foo' => 'Decimal:20|Max:1.88888888888888888888']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.88888888888888888888'], ['foo' => 'Decimal:20|Max:1.88888888888888888888']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            // these are the same number
            'decimal' => '0.555',
            'scientific' => '5.55e-1',
        ], [
            'decimal' => 'Decimal:0,2',
            'scientific' => 'Decimal:0,2',
        ]);
        $this->assertSame(['decimal', 'scientific'], $v->errors()->keys());

        $v = new Validator($trans, [
            // these are the same number
            'decimal' => '0.555',
            'scientific' => '5.55e-1',
        ], [
            'decimal' => 'Decimal:0,3',
            'scientific' => 'Decimal:0,3',
        ]);
        $this->assertSame(['scientific'], $v->errors()->keys());

        $v = new Validator($trans, ['foo' => '+'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->fails());
        $v = new Validator($trans, ['foo' => '-'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->fails());
        $v = new Validator($trans, ['foo' => '10@12'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '+123'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '-123'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '+123.'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '-123.'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '123.'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '123.'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '123.34'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
        $v = new Validator($trans, ['foo' => '123.34'], ['foo' => 'Decimal:0,2']);
        $this->assertTrue($v->passes());
    }

    public function testValidateInt()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Int']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1.23'], ['foo' => 'Int']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '-1'], ['foo' => 'Int']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '1'], ['foo' => 'Int']);
        $this->assertTrue($v->passes());
    }

    public function testValidateDigits()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '12345'], ['foo' => 'Digits:5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Digits:200']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '+2.37'], ['foo' => 'Digits:5']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '2e7'], ['foo' => 'Digits:3']);
        $this->assertTrue($v->fails());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '12345'], ['foo' => 'digits_between:1,6']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'digits_between:1,10']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'digits_between:4,5']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '+12.3'], ['foo' => 'digits_between:1,6']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '12345'], ['foo' => 'min_digits:1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'min_digits:1']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'min_digits:4']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '+12.3'], ['foo' => 'min_digits:1']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '12345'], ['foo' => 'max_digits:6']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'max_digits:10']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'max_digits:2']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '+12.3'], ['foo' => 'max_digits:6']);
        $this->assertFalse($v->passes());
    }

    public function testValidateSize()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Size:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'anc'], ['foo' => 'Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Numeric|Size:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Numeric|Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Decimal:0|Size:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Decimal:0|Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Integer|Size:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Integer|Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Size:4']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(3072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Size:3']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Size:3']);
        $this->assertFalse($v->passes());
    }

    public function testValidateBetween()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'asdad'], ['foo' => 'Between:3,4']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'anc'], ['foo' => 'Between:3,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'ancf'], ['foo' => 'Between:3,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'ancfs'], ['foo' => 'Between:3,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Numeric|Between:50,100']);
        $this->assertFalse($v->passes());

        // inclusive on min
        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Numeric|Between:123,200']);
        $this->assertTrue($v->passes());

        // inclusive on max
        $v = new Validator($trans, ['foo' => '123'], ['foo' => 'Numeric|Between:0,123']);
        $this->assertTrue($v->passes());

        // can work with float
        $v = new Validator($trans, ['foo' => '0.02'], ['foo' => 'Numeric|Between:0.01,0.02']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0.02'], ['foo' => 'Numeric|Between:0.01,0.03']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '0.001'], ['foo' => 'Numeric|Between:0.01,0.03']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Numeric|Between:1,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Between:1,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Between:1,2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(3072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Between:1,5']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Between:1,2']);
        $this->assertFalse($v->passes());
    }

    public function testValidateMin()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Min:3']);
        $this->assertFalse($v->passes());

        // an equal value qualifies.
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Numeric|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'anc'], ['foo' => 'Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2'], ['foo' => 'Numeric|Min:3']);
        $this->assertFalse($v->passes());

        // '2.001' is considered as a float when the "Numeric" rule exists.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Numeric|Min:3']);
        $this->assertFalse($v->passes());

        // '2.001' is a string of length 5 in absence of the "Numeric" rule.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Min:3']);
        $this->assertTrue($v->passes());

        // '20' is a string of length 2 in absence of the "Numeric" rule.
        $v = new Validator($trans, ['foo' => '20'], ['foo' => 'Min:3']);
        $this->assertFalse($v->passes());

        // an equal value qualifies.
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Decimal:0|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2'], ['foo' => 'Decimal:0|Min:3']);
        $this->assertFalse($v->passes());

        // '2.001' is considered as a float when the "Numeric" rule exists.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Decimal:0,3|Min:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '5'], ['foo' => 'Numeric|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3, 4]], ['foo' => 'Array|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2]], ['foo' => 'Array|Min:3']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(3072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Min:2']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(File::class)->onlyMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Min:10']);
        $this->assertFalse($v->passes());
    }

    public function testValidateMax()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'aslksd'], ['foo' => 'Max:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'anc'], ['foo' => 'Max:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '211'], ['foo' => 'Numeric|Max:100']);
        $this->assertFalse($v->passes());

        // an equal value qualifies.
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Numeric|Max:3']);
        $this->assertTrue($v->passes());

        // '2.001' is considered as a float when the "Numeric" rule exists.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Numeric|Max:3']);
        $this->assertTrue($v->passes());

        // '2.001' is a string of length 5 in absence of the "Numeric" rule.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Max:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '211'], ['foo' => 'Decimal:0|Max:100']);
        $this->assertFalse($v->passes());

        // an equal value qualifies.
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Decimal:0|Max:3']);
        $this->assertTrue($v->passes());

        // '2.001' is considered as a float when the "Numeric" rule exists.
        $v = new Validator($trans, ['foo' => '2.001'], ['foo' => 'Decimal:0,3|Max:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '22'], ['foo' => 'Numeric|Max:33']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Max:4']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Max:2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['isValid', 'getSize'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(3072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['isValid', 'getSize'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(4072);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['isValid'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->expects($this->any())->method('isValid')->willReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertFalse($v->passes());
    }

    /**
     * @param  mixed  $input
     * @param  mixed  $allowed
     * @param  bool  $passes
     *
     * @dataProvider multipleOfDataProvider
     */
    public function testValidateMultipleOf($input, $allowed, $passes)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.multiple_of' => 'The :attribute must be a multiple of :value'], 'en');

        $v = new Validator($trans, ['foo' => $input], ['foo' => "multiple_of:{$allowed}"]);

        $this->assertSame($passes, $v->passes());
        if ($v->fails()) {
            $this->assertSame("The foo must be a multiple of {$allowed}", $v->messages()->first('foo'));
        } else {
            $this->assertSame('', $v->messages()->first('foo'));
        }
    }

    public static function multipleOfDataProvider()
    {
        return [
            [0, 0, false], // zero (same)
            [0, 10, true], // zero + integer
            [10, 0, false],
            [0, 10.1, true], // zero + float
            [10.1, 0, false],
            [0, -10, true], // zero + -integer
            [-10, 0, false],
            [0, -10.1, true], // zero + -float
            [-10.1, 0, false],
            [10, 10, true], // integer (same)
            [10, 5, true], // integer + integer
            [10, 4, false],
            [20, 10, true],
            [5, 10, false],
            [10, -5, true], // integer + -integer
            [10, -4, false],
            [-20, 10, true],
            [-5, 10, false],
            [-10, -10, true], // -integer (same)
            [-10, -5, true], // -integer + -integer
            [-10, -4, false],
            [-20, -10, true],
            [-5, -10, false],
            [10, 10.0, true], // integer + float (same)
            [10, 5.0, true], // integer + float
            [10, 4.0, false],
            [20.0, 10, true],
            [5.0, 10, false],
            [10.0, -10.0, true], // integer + -float (same)
            [10, -5.0, true], // integer + -float
            [10, -4.0, false],
            [-20.0, 10, true],
            [-5.0, 10, false],
            [10.0, -10.0, true], // -integer + float (same)
            [-10, 5.0, true], // -integer + float
            [-10, 4.0, false],
            [20.0, -10, true],
            [5.0, -10, false],
            [10.5, 10.5, true], // float (same)
            [10.5, 0.5, true], // float + float
            [10.5, 0.3, true], // 10.5/.3 = 35, tricky for floating point division
            [31.5, 10.5, true],
            [31.6, 10.5, false],
            [10.5, -0.5, true], // float + -float
            [10.5, -0.3, true], // 10.5/.3 = 35, tricky for floating point division
            [-31.5, 10.5, true],
            [-31.6, 10.5, false],
            [-10.5, -10.5, true], // -float (same)
            [-10.5, -0.5, true], // -float + -float
            [-10.5, -0.3, true], // 10.5/.3 = 35, tricky for floating point division
            [-31.5, -10.5, true],
            [-31.6, -10.5, false],
            [2, .1, true], // fmod does this "wrong", it should be 0, but fmod(2, .1) = .1
            [.75, .05, true], // fmod does this "wrong", it should be 0, but fmod(.75, .05) = .05
            [.9, .3, true], // .9/.3 = 3, tricky for floating point division
            ['foo', 1, false], // invalid values
            [1, 'foo', false],
            ['foo', 'foo', false],
            [1, '', false],
            [1, null, false],
        ];
    }

    public function testProperMessagesAreReturnedForSizes()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.min.numeric' => 'numeric', 'validation.size.string' => 'string', 'validation.max.file' => 'file'], 'en');
        $v = new Validator($trans, ['name' => '3'], ['name' => 'Numeric|Min:5']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('numeric', $v->messages()->first('name'));

        $v = new Validator($trans, ['name' => 'asasdfadsfd'], ['name' => 'Size:2']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('string', $v->messages()->first('name'));

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:3']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('file', $v->messages()->first('photo'));
    }

    public function testValidateGtPlaceHolderIsReplacedProperly()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.gt.numeric' => ':value',
            'validation.gt.string' => ':value',
            'validation.gt.file' => ':value',
            'validation.gt.array' => ':value',
        ], 'en');

        $v = new Validator($trans, ['items' => '3'], ['items' => 'gt:4']);
        $this->assertFalse($v->passes());
        $this->assertEquals(4, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 3, 'more' => 5], ['items' => 'numeric|gt:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 'abc', 'more' => 'abcde'], ['items' => 'gt:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('items'));

        $v = new Validator($trans, ['max' => 10], ['min' => 'numeric', 'max' => 'numeric|gt:min'], [], ['min' => 'minimum value', 'max' => 'maximum value']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('minimum value', $v->messages()->first('max'));

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $biggerFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $biggerFile->expects($this->any())->method('getSize')->willReturn(5120);
        $biggerFile->expects($this->any())->method('isValid')->willReturn(true);
        $v = new Validator($trans, ['photo' => $file, 'bigger' => $biggerFile], ['photo' => 'file|gt:bigger']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('photo'));

        $v = new Validator($trans, ['items' => [1, 2, 3], 'more' => [0, 1, 2, 3]], ['items' => 'gt:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(4, $v->messages()->first('items'));
    }

    public function testValidateLtPlaceHolderIsReplacedProperly()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.lt.numeric' => ':value',
            'validation.lt.string' => ':value',
            'validation.lt.file' => ':value',
            'validation.lt.array' => ':value',
        ], 'en');

        $v = new Validator($trans, ['items' => '3'], ['items' => 'lt:2']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 3, 'less' => 2], ['items' => 'numeric|lt:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 'abc', 'less' => 'ab'], ['items' => 'lt:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['min' => 1], ['min' => 'numeric|lt:max', 'max' => 'numeric'], [], ['min' => 'minimum value', 'max' => 'maximum value']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('maximum value', $v->messages()->first('min'));

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $smallerFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $smallerFile->expects($this->any())->method('getSize')->willReturn(2048);
        $smallerFile->expects($this->any())->method('isValid')->willReturn(true);
        $v = new Validator($trans, ['photo' => $file, 'smaller' => $smallerFile], ['photo' => 'file|lt:smaller']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('photo'));

        $v = new Validator($trans, ['items' => [1, 2, 3], 'less' => [0, 1]], ['items' => 'lt:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));
    }

    public function testValidateGtePlaceHolderIsReplacedProperly()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.gte.numeric' => ':value',
            'validation.gte.string' => ':value',
            'validation.gte.file' => ':value',
            'validation.gte.array' => ':value',
        ], 'en');

        $v = new Validator($trans, ['items' => '3'], ['items' => 'gte:4']);
        $this->assertFalse($v->passes());
        $this->assertEquals(4, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 3, 'more' => 5], ['items' => 'numeric|gte:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 'abc', 'more' => 'abcde'], ['items' => 'gte:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('items'));

        $v = new Validator($trans, ['max' => 10], ['min' => 'numeric', 'max' => 'numeric|gte:min'], [], ['min' => 'minimum value', 'max' => 'maximum value']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('minimum value', $v->messages()->first('max'));

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $biggerFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $biggerFile->expects($this->any())->method('getSize')->willReturn(5120);
        $biggerFile->expects($this->any())->method('isValid')->willReturn(true);
        $v = new Validator($trans, ['photo' => $file, 'bigger' => $biggerFile], ['photo' => 'file|gte:bigger']);
        $this->assertFalse($v->passes());
        $this->assertEquals(5, $v->messages()->first('photo'));

        $v = new Validator($trans, ['items' => [1, 2, 3], 'more' => [0, 1, 2, 3]], ['items' => 'gte:more']);
        $this->assertFalse($v->passes());
        $this->assertEquals(4, $v->messages()->first('items'));
    }

    public function testValidateLtePlaceHolderIsReplacedProperly()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.lte.numeric' => ':value',
            'validation.lte.string' => ':value',
            'validation.lte.file' => ':value',
            'validation.lte.array' => ':value',
        ], 'en');

        $v = new Validator($trans, ['items' => '3'], ['items' => 'lte:2']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 3, 'less' => 2], ['items' => 'numeric|lte:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['items' => 'abc', 'less' => 'ab'], ['items' => 'lte:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));

        $v = new Validator($trans, ['min' => 1], ['min' => 'numeric|lte:max', 'max' => 'numeric'], [], ['min' => 'minimum value', 'max' => 'maximum value']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('maximum value', $v->messages()->first('min'));

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(4072);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $smallerFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $smallerFile->expects($this->any())->method('getSize')->willReturn(2048);
        $smallerFile->expects($this->any())->method('isValid')->willReturn(true);
        $v = new Validator($trans, ['photo' => $file, 'smaller' => $smallerFile], ['photo' => 'file|lte:smaller']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('photo'));

        $v = new Validator($trans, ['items' => [1, 2, 3], 'less' => [0, 1]], ['items' => 'lte:less']);
        $this->assertFalse($v->passes());
        $this->assertEquals(2, $v->messages()->first('items'));
    }

    public function testValidateIn()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'In:bar,baz']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 0], ['name' => 'In:bar,baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'In:foo,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => ['foo', 'bar']], ['name' => 'Array|In:foo,baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => ['foo', 'qux']], ['name' => 'Array|In:foo,baz,qux']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => ['foo,bar', 'qux']], ['name' => 'Array|In:"foo,bar",baz,qux']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => 'f"o"o'], ['name' => 'In:"f""o""o",baz,qux']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => "a,b\nc,d"], ['name' => "in:\"a,b\nc,d\""]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => ['foo', 'bar']], ['name' => 'Alpha|In:foo,bar']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['name' => ['foo', []]], ['name' => 'Array|In:foo,bar']);
        $this->assertFalse($v->passes());
    }

    public function testValidateNotIn()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'NotIn:bar,baz']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['name' => 'foo'], ['name' => 'NotIn:foo,baz']);
        $this->assertFalse($v->passes());
    }

    public function testValidateDistinct()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['foo' => ['foo', 'foo']], ['foo.*' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['à', 'À']], ['foo.*' => 'distinct:ignore_case']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['f/oo', 'F/OO']], ['foo.*' => 'distinct:ignore_case']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['1', '1']], ['foo.*' => 'distinct:ignore_case']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['1', '11']], ['foo.*' => 'distinct:ignore_case']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['foo', 'bar']], ['foo.*' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 1], 'baz' => ['id' => 1]]], ['foo.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 'qux'], 'baz' => ['id' => 'QUX']]], ['foo.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 'qux'], 'baz' => ['id' => 'QUX']]], ['foo.*.id' => 'distinct:ignore_case']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 1], 'baz' => ['id' => 2]]], ['foo.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 2], 'baz' => ['id' => 425]]], ['foo.*.id' => 'distinct:ignore_case']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1, 'nested' => ['id' => 1]]]], ['foo.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], ['id' => 1]]], ['foo.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [['id' => 1], ['id' => 2]]], ['foo.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['cat' => [['prod' => [['id' => 1]]], ['prod' => [['id' => 1]]]]], ['cat.*.prod.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['cat' => [['prod' => [['id' => 1]]], ['prod' => [['id' => 2]]]]], ['cat.*.prod.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['cat' => ['sub' => [['prod' => [['id' => 1]]], ['prod' => [['id' => 2]]]]]], ['cat.sub.*.prod.*.id' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['cat' => ['sub' => [['prod' => [['id' => 2]]], ['prod' => [['id' => 2]]]]]], ['cat.sub.*.prod.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['foo', 'foo'], 'bar' => ['bar', 'baz']], ['foo.*' => 'distinct', 'bar.*' => 'distinct']);
        $this->assertFalse($v->passes());
        $this->assertCount(2, $v->messages());

        $v = new Validator($trans, ['foo' => ['foo', 'foo'], 'bar' => ['bar', 'bar']], ['foo.*' => 'distinct', 'bar.*' => 'distinct']);
        $this->assertFalse($v->passes());
        $this->assertCount(4, $v->messages());

        $v->setData(['foo' => ['foo', 'bar'], 'bar' => ['foo', 'bar']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['foo', 'foo']], ['foo.*' => 'distinct'], ['foo.*.distinct' => 'There is a duplication!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('There is a duplication!', $v->messages()->first('foo.0'));
        $this->assertSame('There is a duplication!', $v->messages()->first('foo.1'));

        $v = new Validator($trans, ['foo' => ['0100', '100']], ['foo.*' => 'distinct'], ['foo.*.distinct' => 'There is a duplication!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('There is a duplication!', $v->messages()->first('foo.0'));
        $this->assertSame('There is a duplication!', $v->messages()->first('foo.1'));

        $v = new Validator($trans, ['foo' => ['0100', '100']], ['foo.*' => 'distinct:strict']);
        $this->assertTrue($v->passes());
    }

    public function testValidateDistinctForTopLevelArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['foo', 'foo'], ['*' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [['foo' => 1], ['foo' => 1]], ['*' => 'array', '*.foo' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [['foo' => 'a'], ['foo' => 'A']], ['*' => 'array', '*.foo' => 'distinct:ignore_case']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [['foo' => [['id' => 1]]], ['foo' => [['id' => 1]]]], ['*' => 'array', '*.foo' => 'array', '*.foo.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo', 'foo'], ['*' => 'distinct'], ['*.distinct' => 'There is a duplication!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('There is a duplication!', $v->messages()->first('0'));
        $this->assertSame('There is a duplication!', $v->messages()->first('1'));
    }

    public function testValidateUnique()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:connection.users']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with('connection');
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,1']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id', [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,1,id_col']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id_col', [])->andReturn(2);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['users' => [['id' => 1, 'email' => 'foo']]], ['users.*.email' => 'Unique:users,email,[users.*.id]']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', '1', 'id', [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,NULL,id_col,foo,bar']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->withArgs(function () {
            return func_get_args() === ['users', 'email_addr', 'foo', null, 'id_col', ['foo' => 'bar']];
        })->andReturn(2);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());
    }

    public function testValidateUniqueAndExistsSendsCorrectFieldNameToDBWithArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [['email' => 'foo', 'type' => 'bar']], [
            '*.email' => 'unique:users', '*.type' => 'exists:user_types',
        ]);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->twice()->with(null);
        $mock->shouldReceive('getCount')->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $mock->shouldReceive('getCount')->with('user_types', 'type', 'bar', null, null, [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $closure = function () {
            //
        };
        $v = new Validator($trans, [['email' => 'foo', 'type' => 'bar']], [
            '*.email' => (new Unique('users'))->where($closure),
            '*.type' => (new Exists('user_types'))->where($closure),
        ]);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->twice()->with(null);
        $mock->shouldReceive('getCount')->with('users', 'email', 'foo', null, 'id', [$closure])->andReturn(0);
        $mock->shouldReceive('getCount')->with('user_types', 'type', 'bar', null, null, [$closure])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());
    }

    public function testValidationExists()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:users']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:users,email,account_id,1,name,taylor']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, ['account_id' => 1, 'name' => 'taylor'])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:users,email_addr']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', null, null, [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => ['foo']], ['email' => 'Exists:users,email_addr']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getMultiCount')->once()->with('users', 'email_addr', ['foo'], [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:connection.users']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with('connection');
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => ['foo', 'foo']], ['email' => 'exists:users,email_addr']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getMultiCount')->once()->with('users', 'email_addr', ['foo', 'foo'], [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());
    }

    public function testValidationExistsIsNotCalledUnnecessarily()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => 'foo'], ['id' => 'Integer|Exists:users,id']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('getCount')->never();
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => '1'], ['id' => 'Integer|Exists:users,id']);
        $mock = m::mock(DatabasePresenceVerifierInterface::class);
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'id', '1', null, null, [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());
    }

    public function testValidateGtMessagesAreCorrect()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.gt.numeric' => 'The :attribute field must be greater than :value.',
            'validation.gt.string' => 'The :attribute field must be greater than :value characters.',
            'validation.gt.file' => 'The :attribute field must be greater than :value kilobytes.',
            'validation.gt.array' => 'The :attribute field must have more than :value items.',
        ], 'en');

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(8919);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $otherFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $otherFile->expects($this->any())->method('getSize')->willReturn(9216);
        $otherFile->expects($this->any())->method('isValid')->willReturn(true);

        $v = new Validator($trans, [
            'numeric' => 7,
            'string' => 'abcd',
            'file' => $file,
            'array' => [1, 2, 3],
            'other_numeric' => 10,
            'other_string' => 'abcde',
            'other_file' => $otherFile,
            'other_array' => [1, 2, 3, 4],
        ], [
            'numeric' => 'gt:other_numeric',
            'string' => 'gt:other_string',
            'file' => 'gt:other_file',
            'array' => 'array|gt:other_array',
        ]);

        $this->assertFalse($v->passes());
        $this->assertEquals('The numeric field must be greater than 10.', $v->messages()->first('numeric'));
        $this->assertEquals('The string field must be greater than 5 characters.', $v->messages()->first('string'));
        $this->assertEquals('The file field must be greater than 9 kilobytes.', $v->messages()->first('file'));
        $this->assertEquals('The array field must have more than 4 items.', $v->messages()->first('array'));
    }

    public function testValidateGteMessagesAreCorrect()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.gte.numeric' => 'The :attribute field must be greater than or equal to :value.',
            'validation.gte.string' => 'The :attribute field must be greater than or equal to :value characters.',
            'validation.gte.file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
            'validation.gte.array' => 'The :attribute field must have :value items or more.',
        ], 'en');

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(8919);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $otherFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $otherFile->expects($this->any())->method('getSize')->willReturn(9216);
        $otherFile->expects($this->any())->method('isValid')->willReturn(true);

        $v = new Validator($trans, [
            'numeric' => 7,
            'string' => 'abcd',
            'file' => $file,
            'array' => [1, 2, 3],
            'other_numeric' => 10,
            'other_string' => 'abcde',
            'other_file' => $otherFile,
            'other_array' => [1, 2, 3, 4],
        ], [
            'numeric' => 'gte:other_numeric',
            'string' => 'gte:other_string',
            'file' => 'gte:other_file',
            'array' => 'array|gte:other_array',
        ]);

        $this->assertFalse($v->passes());
        $this->assertEquals('The numeric field must be greater than or equal to 10.', $v->messages()->first('numeric'));
        $this->assertEquals('The string field must be greater than or equal to 5 characters.', $v->messages()->first('string'));
        $this->assertEquals('The file field must be greater than or equal to 9 kilobytes.', $v->messages()->first('file'));
        $this->assertEquals('The array field must have 4 items or more.', $v->messages()->first('array'));
    }

    public function testValidateLtMessagesAreCorrect()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.lt.numeric' => 'The :attribute field must be less than :value.',
            'validation.lt.string' => 'The :attribute field must be less than :value characters.',
            'validation.lt.file' => 'The :attribute field must be less than :value kilobytes.',
            'validation.lt.array' => 'The :attribute field must have less than :value items.',
        ], 'en');

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(8919);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $otherFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $otherFile->expects($this->any())->method('getSize')->willReturn(8192);
        $otherFile->expects($this->any())->method('isValid')->willReturn(true);

        $v = new Validator($trans, [
            'numeric' => 7,
            'string' => 'abcd',
            'file' => $file,
            'array' => [1, 2, 3],
            'other_numeric' => 5,
            'other_string' => 'abc',
            'other_file' => $otherFile,
            'other_array' => [1, 2],
        ], [
            'numeric' => 'lt:other_numeric',
            'string' => 'lt:other_string',
            'file' => 'lt:other_file',
            'array' => 'array|lt:other_array',
        ]);

        $this->assertFalse($v->passes());
        $this->assertEquals('The numeric field must be less than 5.', $v->messages()->first('numeric'));
        $this->assertEquals('The string field must be less than 3 characters.', $v->messages()->first('string'));
        $this->assertEquals('The file field must be less than 8 kilobytes.', $v->messages()->first('file'));
        $this->assertEquals('The array field must have less than 2 items.', $v->messages()->first('array'));
    }

    public function testValidateLteMessagesAreCorrect()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.lte.numeric' => 'The :attribute field must be less than or equal to :value.',
            'validation.lte.string' => 'The :attribute field must be less than or equal to :value characters.',
            'validation.lte.file' => 'The :attribute field must be less than or equal to :value kilobytes.',
            'validation.lte.array' => 'The :attribute field must not have more than :value items.',
        ], 'en');

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->willReturn(8919);
        $file->expects($this->any())->method('isValid')->willReturn(true);
        $otherFile = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $otherFile->expects($this->any())->method('getSize')->willReturn(8192);
        $otherFile->expects($this->any())->method('isValid')->willReturn(true);

        $v = new Validator($trans, [
            'numeric' => 7,
            'string' => 'abcd',
            'file' => $file,
            'array' => [1, 2, 3],
            'other_numeric' => 5,
            'other_string' => 'abc',
            'other_file' => $otherFile,
            'other_array' => [1, 2],
        ], [
            'numeric' => 'lte:other_numeric',
            'string' => 'lte:other_string',
            'file' => 'lte:other_file',
            'array' => 'array|lte:other_array',
        ]);

        $this->assertFalse($v->passes());
        $this->assertEquals('The numeric field must be less than or equal to 5.', $v->messages()->first('numeric'));
        $this->assertEquals('The string field must be less than or equal to 3 characters.', $v->messages()->first('string'));
        $this->assertEquals('The file field must be less than or equal to 8 kilobytes.', $v->messages()->first('file'));
        $this->assertEquals('The array field must not have more than 2 items.', $v->messages()->first('array'));
    }

    public function testValidateIp()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['ip' => 'aslsdlks'], ['ip' => 'Ip']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['ip' => '127.0.0.1'], ['ip' => 'Ip']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['ip' => '127.0.0.1'], ['ip' => 'Ipv4']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['ip' => '::1'], ['ip' => 'Ipv6']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['ip' => '127.0.0.1'], ['ip' => 'Ipv6']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['ip' => '::1'], ['ip' => 'Ipv4']);
        $this->assertTrue($v->fails());
    }

    public function testValidateMacAddress()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => 'foo'], ['mac' => 'mac_address']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01-23-45-67-89-ab'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01-23-45-67-89-AB'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01-23-45-67-89-aB'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01:23:45:67:89:ab'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01:23:45:67:89:AB'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01:23:45:67:89:aB'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '01:23:45-67:89:aB'], ['mac' => 'mac_address']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => 'xx:23:45:67:89:aB'], ['mac' => 'mac_address']);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['mac' => '0123.4567.89ab'], ['mac' => 'mac_address']);
        $this->assertTrue($v->passes());
    }

    public function testValidateEmail()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'Email']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => ['not a string']], ['x' => 'Email']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [
            'x' => new class
            {
                public function __toString()
                {
                    return 'aslsdlks';
                }
            },
        ], ['x' => 'Email']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [
            'x' => new class
            {
                public function __toString()
                {
                    return 'foo@gmail.com';
                }
            },
        ], ['x' => 'Email']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'foo@gmail.com'], ['x' => 'Email']);
        $this->assertTrue($v->passes());
    }

    public function testValidateEmailWithInternationalCharacters()
    {
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'foo@gmäil.com'], ['x' => 'email']);
        $this->assertTrue($v->passes());
    }

    public function testValidateEmailWithStrictCheck()
    {
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'foo@bar '], ['x' => 'email:strict']);
        $this->assertFalse($v->passes());
    }

    public function testValidateEmailWithFilterCheck()
    {
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'foo@bar'], ['x' => 'email:filter']);
        $this->assertFalse($v->passes());

        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'example@example.com'], ['x' => 'email:filter']);
        $this->assertTrue($v->passes());

        // Unicode characters are not allowed
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'exämple@example.com'], ['x' => 'email:filter']);
        $this->assertFalse($v->passes());

        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'exämple@exämple.com'], ['x' => 'email:filter']);
        $this->assertFalse($v->passes());
    }

    public function testValidateEmailWithFilterUnicodeCheck()
    {
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'foo@bar'], ['x' => 'email:filter_unicode']);
        $this->assertFalse($v->passes());

        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'example@example.com'], ['x' => 'email:filter_unicode']);
        $this->assertTrue($v->passes());

        // Any unicode characters are allowed only in local-part
        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'exämple@example.com'], ['x' => 'email:filter_unicode']);
        $this->assertTrue($v->passes());

        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'exämple@exämple.com'], ['x' => 'email:filter_unicode']);
        $this->assertFalse($v->passes());
    }

    public function testValidateEmailWithCustomClassCheck()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with(NoRFCWarningsValidation::class)->andReturn(new NoRFCWarningsValidation);

        $v = new Validator($this->getIlluminateArrayTranslator(), ['x' => 'foo@bar '], ['x' => 'email:'.NoRFCWarningsValidation::class]);
        $v->setContainer($container);

        $this->assertFalse($v->passes());
    }

    public function testValidateUrlWithProtocols()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // Allow a non-standard protocol
        $v = new Validator($trans, ['x' => 'foo://bar'], ['x' => 'url:https,foo']);
        $this->assertTrue($v->passes());

        // Test with a standard protocol
        $v = new Validator($trans, ['x' => 'http://laravel.com'], ['x' => 'url:https']);
        $this->assertFalse($v->passes());
    }

    /**
     * @dataProvider validUrls
     */
    public function testValidateUrlWithValidUrls($validUrl)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => $validUrl], ['x' => 'Url']);
        $this->assertTrue($v->passes());
    }

    /**
     * @dataProvider invalidUrls
     */
    public function testValidateUrlWithInvalidUrls($invalidUrl)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => $invalidUrl], ['x' => 'Url']);
        $this->assertFalse($v->passes());
    }

    public static function validUrls()
    {
        return [
            ['aaa://fully.qualified.domain/path'],
            ['aaas://fully.qualified.domain/path'],
            ['about://fully.qualified.domain/path'],
            ['acap://fully.qualified.domain/path'],
            ['acct://fully.qualified.domain/path'],
            ['acr://fully.qualified.domain/path'],
            ['adiumxtra://fully.qualified.domain/path'],
            ['afp://fully.qualified.domain/path'],
            ['afs://fully.qualified.domain/path'],
            ['aim://fully.qualified.domain/path'],
            ['apt://fully.qualified.domain/path'],
            ['attachment://fully.qualified.domain/path'],
            ['aw://fully.qualified.domain/path'],
            ['barion://fully.qualified.domain/path'],
            ['beshare://fully.qualified.domain/path'],
            ['bitcoin://fully.qualified.domain/path'],
            ['blob://fully.qualified.domain/path'],
            ['bolo://fully.qualified.domain/path'],
            ['callto://fully.qualified.domain/path'],
            ['cap://fully.qualified.domain/path'],
            ['chrome://fully.qualified.domain/path'],
            ['chrome-extension://fully.qualified.domain/path'],
            ['cid://fully.qualified.domain/path'],
            ['coap://fully.qualified.domain/path'],
            ['coaps://fully.qualified.domain/path'],
            ['com-eventbrite-attendee://fully.qualified.domain/path'],
            ['content://fully.qualified.domain/path'],
            ['crid://fully.qualified.domain/path'],
            ['cvs://fully.qualified.domain/path'],
            ['data://fully.qualified.domain/path'],
            ['dav://fully.qualified.domain/path'],
            ['dict://fully.qualified.domain/path'],
            ['dlna-playcontainer://fully.qualified.domain/path'],
            ['dlna-playsingle://fully.qualified.domain/path'],
            ['dns://fully.qualified.domain/path'],
            ['dntp://fully.qualified.domain/path'],
            ['dtn://fully.qualified.domain/path'],
            ['dvb://fully.qualified.domain/path'],
            ['ed2k://fully.qualified.domain/path'],
            ['example://fully.qualified.domain/path'],
            ['facetime://fully.qualified.domain/path'],
            ['fax://fully.qualified.domain/path'],
            ['feed://fully.qualified.domain/path'],
            ['feedready://fully.qualified.domain/path'],
            ['file://fully.qualified.domain/path'],
            ['filesystem://fully.qualified.domain/path'],
            ['finger://fully.qualified.domain/path'],
            ['fish://fully.qualified.domain/path'],
            ['ftp://fully.qualified.domain/path'],
            ['geo://fully.qualified.domain/path'],
            ['gg://fully.qualified.domain/path'],
            ['git://fully.qualified.domain/path'],
            ['gizmoproject://fully.qualified.domain/path'],
            ['go://fully.qualified.domain/path'],
            ['gopher://fully.qualified.domain/path'],
            ['gtalk://fully.qualified.domain/path'],
            ['h323://fully.qualified.domain/path'],
            ['ham://fully.qualified.domain/path'],
            ['hcp://fully.qualified.domain/path'],
            ['http://fully.qualified.domain/path'],
            ['https://fully.qualified.domain/path'],
            ['iax://fully.qualified.domain/path'],
            ['icap://fully.qualified.domain/path'],
            ['icon://fully.qualified.domain/path'],
            ['im://fully.qualified.domain/path'],
            ['imap://fully.qualified.domain/path'],
            ['info://fully.qualified.domain/path'],
            ['iotdisco://fully.qualified.domain/path'],
            ['ipn://fully.qualified.domain/path'],
            ['ipp://fully.qualified.domain/path'],
            ['ipps://fully.qualified.domain/path'],
            ['irc://fully.qualified.domain/path'],
            ['irc6://fully.qualified.domain/path'],
            ['ircs://fully.qualified.domain/path'],
            ['iris://fully.qualified.domain/path'],
            ['iris.beep://fully.qualified.domain/path'],
            ['iris.lwz://fully.qualified.domain/path'],
            ['iris.xpc://fully.qualified.domain/path'],
            ['iris.xpcs://fully.qualified.domain/path'],
            ['itms://fully.qualified.domain/path'],
            ['jabber://fully.qualified.domain/path'],
            ['jar://fully.qualified.domain/path'],
            ['jms://fully.qualified.domain/path'],
            ['keyparc://fully.qualified.domain/path'],
            ['lastfm://fully.qualified.domain/path'],
            ['ldap://fully.qualified.domain/path'],
            ['ldaps://fully.qualified.domain/path'],
            ['magnet://fully.qualified.domain/path'],
            ['mailserver://fully.qualified.domain/path'],
            ['mailto://fully.qualified.domain/path'],
            ['maps://fully.qualified.domain/path'],
            ['market://fully.qualified.domain/path'],
            ['message://fully.qualified.domain/path'],
            ['mid://fully.qualified.domain/path'],
            ['mms://fully.qualified.domain/path'],
            ['modem://fully.qualified.domain/path'],
            ['ms-help://fully.qualified.domain/path'],
            ['ms-settings://fully.qualified.domain/path'],
            ['ms-settings-airplanemode://fully.qualified.domain/path'],
            ['ms-settings-bluetooth://fully.qualified.domain/path'],
            ['ms-settings-camera://fully.qualified.domain/path'],
            ['ms-settings-cellular://fully.qualified.domain/path'],
            ['ms-settings-cloudstorage://fully.qualified.domain/path'],
            ['ms-settings-emailandaccounts://fully.qualified.domain/path'],
            ['ms-settings-language://fully.qualified.domain/path'],
            ['ms-settings-location://fully.qualified.domain/path'],
            ['ms-settings-lock://fully.qualified.domain/path'],
            ['ms-settings-nfctransactions://fully.qualified.domain/path'],
            ['ms-settings-notifications://fully.qualified.domain/path'],
            ['ms-settings-power://fully.qualified.domain/path'],
            ['ms-settings-privacy://fully.qualified.domain/path'],
            ['ms-settings-proximity://fully.qualified.domain/path'],
            ['ms-settings-screenrotation://fully.qualified.domain/path'],
            ['ms-settings-wifi://fully.qualified.domain/path'],
            ['ms-settings-workplace://fully.qualified.domain/path'],
            ['msnim://fully.qualified.domain/path'],
            ['msrp://fully.qualified.domain/path'],
            ['msrps://fully.qualified.domain/path'],
            ['mtqp://fully.qualified.domain/path'],
            ['mumble://fully.qualified.domain/path'],
            ['mupdate://fully.qualified.domain/path'],
            ['mvn://fully.qualified.domain/path'],
            ['news://fully.qualified.domain/path'],
            ['nfs://fully.qualified.domain/path'],
            ['ni://fully.qualified.domain/path'],
            ['nih://fully.qualified.domain/path'],
            ['nntp://fully.qualified.domain/path'],
            ['notes://fully.qualified.domain/path'],
            ['oid://fully.qualified.domain/path'],
            ['opaquelocktoken://fully.qualified.domain/path'],
            ['pack://fully.qualified.domain/path'],
            ['palm://fully.qualified.domain/path'],
            ['paparazzi://fully.qualified.domain/path'],
            ['pkcs11://fully.qualified.domain/path'],
            ['platform://fully.qualified.domain/path'],
            ['pop://fully.qualified.domain/path'],
            ['pres://fully.qualified.domain/path'],
            ['prospero://fully.qualified.domain/path'],
            ['proxy://fully.qualified.domain/path'],
            ['psyc://fully.qualified.domain/path'],
            ['query://fully.qualified.domain/path'],
            ['redis://fully.qualified.domain/path'],
            ['rediss://fully.qualified.domain/path'],
            ['reload://fully.qualified.domain/path'],
            ['res://fully.qualified.domain/path'],
            ['resource://fully.qualified.domain/path'],
            ['rmi://fully.qualified.domain/path'],
            ['rsync://fully.qualified.domain/path'],
            ['rtmfp://fully.qualified.domain/path'],
            ['rtmp://fully.qualified.domain/path'],
            ['rtsp://fully.qualified.domain/path'],
            ['rtsps://fully.qualified.domain/path'],
            ['rtspu://fully.qualified.domain/path'],
            ['s3://fully.qualified.domain/path'],
            ['secondlife://fully.qualified.domain/path'],
            ['service://fully.qualified.domain/path'],
            ['session://fully.qualified.domain/path'],
            ['sftp://fully.qualified.domain/path'],
            ['sgn://fully.qualified.domain/path'],
            ['shttp://fully.qualified.domain/path'],
            ['sieve://fully.qualified.domain/path'],
            ['sip://fully.qualified.domain/path'],
            ['sips://fully.qualified.domain/path'],
            ['skype://fully.qualified.domain/path'],
            ['smb://fully.qualified.domain/path'],
            ['sms://fully.qualified.domain/path'],
            ['smtp://fully.qualified.domain/path'],
            ['snews://fully.qualified.domain/path'],
            ['snmp://fully.qualified.domain/path'],
            ['soap.beep://fully.qualified.domain/path'],
            ['soap.beeps://fully.qualified.domain/path'],
            ['soldat://fully.qualified.domain/path'],
            ['spotify://fully.qualified.domain/path'],
            ['ssh://fully.qualified.domain/path'],
            ['steam://fully.qualified.domain/path'],
            ['stun://fully.qualified.domain/path'],
            ['stuns://fully.qualified.domain/path'],
            ['submit://fully.qualified.domain/path'],
            ['svn://fully.qualified.domain/path'],
            ['tag://fully.qualified.domain/path'],
            ['teamspeak://fully.qualified.domain/path'],
            ['tel://fully.qualified.domain/path'],
            ['teliaeid://fully.qualified.domain/path'],
            ['telnet://fully.qualified.domain/path'],
            ['tftp://fully.qualified.domain/path'],
            ['things://fully.qualified.domain/path'],
            ['thismessage://fully.qualified.domain/path'],
            ['tip://fully.qualified.domain/path'],
            ['tn3270://fully.qualified.domain/path'],
            ['turn://fully.qualified.domain/path'],
            ['turns://fully.qualified.domain/path'],
            ['tv://fully.qualified.domain/path'],
            ['udp://fully.qualified.domain/path'],
            ['unreal://fully.qualified.domain/path'],
            ['urn://fully.qualified.domain/path'],
            ['ut2004://fully.qualified.domain/path'],
            ['vemmi://fully.qualified.domain/path'],
            ['ventrilo://fully.qualified.domain/path'],
            ['videotex://fully.qualified.domain/path'],
            ['view-source://fully.qualified.domain/path'],
            ['wais://fully.qualified.domain/path'],
            ['webcal://fully.qualified.domain/path'],
            ['ws://fully.qualified.domain/path'],
            ['wss://fully.qualified.domain/path'],
            ['wtai://fully.qualified.domain/path'],
            ['wyciwyg://fully.qualified.domain/path'],
            ['xcon://fully.qualified.domain/path'],
            ['xcon-userid://fully.qualified.domain/path'],
            ['xfire://fully.qualified.domain/path'],
            ['xmlrpc.beep://fully.qualified.domain/path'],
            ['xmlrpc.beeps://fully.qualified.domain/path'],
            ['xmpp://fully.qualified.domain/path'],
            ['xri://fully.qualified.domain/path'],
            ['ymsgr://fully.qualified.domain/path'],
            ['z39.50://fully.qualified.domain/path'],
            ['z39.50r://fully.qualified.domain/path'],
            ['z39.50s://fully.qualified.domain/path'],
            ['http://a.pl'],
            ['http://localhost/url.php'],
            ['http://local.dev'],
            ['http://google.com'],
            ['http://www.google.com'],
            ['http://goog_le.com'],
            ['https://google.com'],
            ['http://illuminate.dev'],
            ['http://localhost'],
            ['https://laravel.com/?'],
            ['http://президент.рф/'],
            ['http://스타벅스코리아.com'],
            ['http://xn--d1abbgf6aiiy.xn--p1ai/'],
            ['https://laravel.com?'],
            ['https://laravel.com?q=1'],
            ['https://laravel.com/?q=1'],
            ['https://laravel.com#'],
            ['https://laravel.com#fragment'],
            ['https://laravel.com/#fragment'],
            ['https://domain1'],
            ['https://domain12/'],
            ['https://domain12#fragment'],
            ['https://domain1/path'],
            ['https://domain.com/path/%2528failed%2526?param=1#fragment'],
        ];
    }

    public static function invalidUrls()
    {
        return [
            ['aslsdlks'],
            ['google.com'],
            ['://google.com'],
            ['http ://google.com'],
            ['http:/google.com'],
            ['http://google.com::aa'],
            ['http://google.com:aa'],
            ['http://127.0.0.1:aa'],
            ['http://[::1'],
            ['foo://bar'],
            ['javascript://test%0Aalert(321)'],
        ];
    }

    /**
     * @dataProvider activeUrlDataProvider
     */
    public function testValidateActiveUrl($data, $outcome)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = m::mock(
            new Validator($trans, $data, ['x' => 'active_url']),
            function (MockInterface $mock) {
                $mock
                    ->shouldAllowMockingProtectedMethods()
                    ->shouldReceive('getDnsRecords')
                    ->withAnyArgs()
                    ->zeroOrMoreTimes()
                    ->andReturn(['hit']);
            }
        );
        $this->assertEquals($outcome, $v->passes());
    }

    public static function activeUrlDataProvider()
    {
        return [
            'Invalid Url' => [
                ['x' => 'aslsdlks'],
                false,
            ],
            'Invalid Urls' => [
                ['x' => 'fdsfs', 'fdsfds'],
                false,
            ],
            'Google Without Subdomain' => [
                ['x' => 'http://google.com'],
                true,
            ],
            'Google With Subdomain' => [
                ['x' => 'http://www.google.com'],
                true,
            ],
            'Google With Subdomain About Page' => [
                ['x' => 'http://www.google.com/about'],
                true,
            ],
        ];
    }

    public function testValidateImage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('php');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('php');
        $v = new Validator($trans, ['x' => $file], ['x' => 'image']);
        $this->assertFalse($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('jpeg');
        $file2->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpeg');
        $v = new Validator($trans, ['x' => $file2], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file2->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file2], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file2->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file2], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file3 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file3->expects($this->any())->method('guessExtension')->willReturn('gif');
        $file3->expects($this->any())->method('getClientOriginalExtension')->willReturn('gif');
        $v = new Validator($trans, ['x' => $file3], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file4 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file4->expects($this->any())->method('guessExtension')->willReturn('bmp');
        $file4->expects($this->any())->method('getClientOriginalExtension')->willReturn('bmp');
        $v = new Validator($trans, ['x' => $file4], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file5 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file5->expects($this->any())->method('guessExtension')->willReturn('png');
        $file5->expects($this->any())->method('getClientOriginalExtension')->willReturn('png');
        $v = new Validator($trans, ['x' => $file5], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file6 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file6->expects($this->any())->method('guessExtension')->willReturn('svg');
        $file6->expects($this->any())->method('getClientOriginalExtension')->willReturn('svg');
        $v = new Validator($trans, ['x' => $file6], ['x' => 'image']);
        $this->assertTrue($v->passes());

        $file7 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file7->expects($this->any())->method('guessExtension')->willReturn('webp');
        $file7->expects($this->any())->method('getClientOriginalExtension')->willReturn('webp');

        $v = new Validator($trans, ['x' => $file7], ['x' => 'Image']);
        $this->assertTrue($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file2->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file2], ['x' => 'Image']);
        $this->assertTrue($v->passes());
    }

    public function testValidateImageDoesNotAllowPhpExtensionsOnImageMime()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('jpeg');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('php');
        $v = new Validator($trans, ['x' => $file], ['x' => 'image']);
        $this->assertFalse($v->passes());
    }

    public function testValidateImageDimensions()
    {
        // Knowing that demo image.png has width = 3 and height = 2
        $uploadedFile = new UploadedFile(__DIR__.'/fixtures/image.png', '', null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => 'file'], ['x' => 'dimensions']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=5']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:max_width=10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:max_width=1']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_height=1']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_height=5']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:max_height=10']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:max_height=1']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:width=3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:height=2']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_height=2,ratio=3/2']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=1.5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=1/1']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=1']);
        $this->assertTrue($v->fails());

        // Knowing that demo image2.png has width = 4 and height = 2
        $uploadedFile = new UploadedFile(__DIR__.'/fixtures/image2.png', '', null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        // Ensure validation doesn't erroneously fail when ratio has no fractional part
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=2/1']);
        $this->assertTrue($v->passes());

        // This test fails without suppressing warnings on getimagesize() due to a read error.
        $emptyUploadedFile = new UploadedFile(__DIR__.'/fixtures/empty.png', '', null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $emptyUploadedFile], ['x' => 'dimensions:min_width=1']);
        $this->assertTrue($v->fails());

        // Knowing that demo image3.png has width = 7 and height = 10
        $uploadedFile = new UploadedFile(__DIR__.'/fixtures/image3.png', '', null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        // Ensure validation doesn't erroneously fail when ratio has no fractional part
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=2/3']);
        $this->assertTrue($v->passes());

        // Ensure svg images always pass as size is irrelevant (image/svg+xml)
        $svgXmlUploadedFile = new UploadedFile(__DIR__.'/fixtures/image.svg', '', 'image/svg+xml', null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $svgXmlUploadedFile], ['x' => 'dimensions:max_width=1,max_height=1']);
        $this->assertTrue($v->passes());

        $svgXmlFile = new File(__DIR__.'/fixtures/image.svg', '', 'image/svg+xml', null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $svgXmlFile], ['x' => 'dimensions:max_width=1,max_height=1']);
        $this->assertTrue($v->passes());

        // Ensure svg images always pass as size is irrelevant (image/svg)
        $svgUploadedFile = new UploadedFile(__DIR__.'/fixtures/image2.svg', '', 'image/svg', null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $svgUploadedFile], ['x' => 'dimensions:max_width=1,max_height=1']);
        $this->assertTrue($v->passes());

        $svgFile = new File(__DIR__.'/fixtures/image2.svg', '', 'image/svg', null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $svgFile], ['x' => 'dimensions:max_width=1,max_height=1']);
        $this->assertTrue($v->passes());

        // Knowing that demo image4.png has width = 64 and height = 65
        $uploadedFile = new UploadedFile(__DIR__.'/fixtures/image4.png', '', null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        // Ensure validation doesn't erroneously fail when ratio doesn't matches
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=1']);
        $this->assertFalse($v->passes());
    }

    public function testValidateMimetypes()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $uploadedFile = [__DIR__.'/ValidationMacroTest.php', '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('rtf');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('rtf');

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getMimeType'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getMimeType')->willReturn('text/rtf');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimetypes:text/*']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getMimeType'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getMimeType')->willReturn('application/pdf');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimetypes:text/rtf']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getMimeType'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getMimeType')->willReturn('image/jpeg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimetypes:image/jpeg']);
        $this->assertTrue($v->passes());
    }

    public function testValidateMime()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('pdf');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('pdf');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:pdf']);
        $this->assertTrue($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'isValid'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('pdf');
        $file2->expects($this->any())->method('isValid')->willReturn(false);
        $v = new Validator($trans, ['x' => $file2], ['x' => 'mimes:pdf']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:jpeg']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpeg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:jpg']);
        $this->assertTrue($v->passes());
    }

    public function testValidateExtension()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('pdf');
        $v = new Validator($trans, ['x' => $file], ['x' => 'extensions:pdf']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'extensions:jpg']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'extensions:jpeg,jpg']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'extensions:jpeg']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('jpg');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('jpeg');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:jpg|extensions:jpg']);
        $this->assertFalse($v->passes());
    }

    public function testValidateMimeEnforcesPhpCheck()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, true];

        $file = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->willReturn('pdf');
        $file->expects($this->any())->method('getClientOriginalExtension')->willReturn('php');
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:pdf']);
        $this->assertFalse($v->passes());

        $file2 = $this->getMockBuilder(UploadedFile::class)->onlyMethods(['guessExtension', 'getClientOriginalExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->willReturn('php');
        $file2->expects($this->any())->method('getClientOriginalExtension')->willReturn('php');
        $v = new Validator($trans, ['x' => $file2], ['x' => 'mimes:pdf,php']);
        $this->assertTrue($v->passes());
    }

    /**
     * @requires extension fileinfo
     */
    public function testValidateFile()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $file = new UploadedFile(__FILE__, '', null, null, true);

        $v = new Validator($trans, ['x' => '1'], ['x' => 'file']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => $file], ['x' => 'file']);
        $this->assertTrue($v->passes());
    }

    public function testEmptyRulesSkipped()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => ['alpha', [], '']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => '|||required|']);
        $this->assertTrue($v->passes());
    }

    public function testAlternativeFormat()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => ['alpha', ['min', 3], ['max', 10]]]);
        $this->assertTrue($v->passes());
    }

    public function testValidateAlpha()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'Alpha']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'x' => 'aslsdlks
1
1',
        ], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'http://google.com'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコードを基盤技術と'], ['x' => 'Alpha']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコード を基盤技術と'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार'], ['x' => 'Alpha']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'आपका स्वागत है'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'Continuación'], ['x' => 'Alpha']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'ofreció su dimisión'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '❤'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '123'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 123], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'abc123'], ['x' => 'Alpha']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'Alpha']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateAlphaNum()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asls13dlks'], ['x' => 'AlphaNum']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://g232oogle.com'], ['x' => 'AlphaNum']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '१२३'], ['x' => 'AlphaNum']); // numbers in Hindi
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '٧٨٩'], ['x' => 'AlphaNum']); // eastern arabic numerals
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार'], ['x' => 'AlphaNum']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'AlphaNum']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateAlphaDash()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asls1-_3dlks'], ['x' => 'AlphaDash']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://-g232oogle.com'], ['x' => 'AlphaDash']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार-_'], ['x' => 'AlphaDash']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '٧٨٩'], ['x' => 'AlphaDash']); // eastern arabic numerals
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'AlphaDash']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateAlphaWithAsciiOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'Alpha:ascii']);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'x' => 'aslsdlks
1
1',
        ], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'http://google.com'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコードを基盤技術と'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコード を基盤技術と'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'आपका स्वागत है'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'Continuación'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ofreció su dimisión'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '❤'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '123'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 123], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'abc123'], ['x' => 'Alpha:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'Alpha:ascii']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateAlphaNumWithAsciiOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asls13dlks'], ['x' => 'AlphaNum:ascii']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://g232oogle.com'], ['x' => 'AlphaNum:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコードを基盤技術と123'], ['x' => 'AlphaNum:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '१२३'], ['x' => 'AlphaNum:ascii']); // numbers in Hindi
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '٧٨٩'], ['x' => 'AlphaNum:ascii']); // eastern arabic numerals
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार'], ['x' => 'AlphaNum:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'AlphaNum:ascii']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateAlphaDashWithAsciiOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asls1-_3dlks'], ['x' => 'AlphaDash:ascii']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://-g232oogle.com'], ['x' => 'AlphaDash:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'ユニコードを基盤技術と-_123'], ['x' => 'AlphaDash:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'नमस्कार-_'], ['x' => 'AlphaDash:ascii']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '٧٨٩'], ['x' => 'AlphaDash:ascii']); // eastern arabic numerals
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => "abc\n"], ['x' => 'AlphaDash:ascii']); // ends with newline
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezone()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'africa/windhoek'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAfricaOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Africa']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'America/New_York'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'africa/windhoek'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Africa']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAmericaOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'America/New_York'], ['foo' => 'Timezone:America']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'america/new_york'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:America']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAntarcticaOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Antarctica/Mawson'], ['foo' => 'Timezone:Antarctica']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'antarctica/mawson'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Antarctica']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithArcticOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Arctic/Longyearbyen'], ['foo' => 'Timezone:Arctic']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'arctic/longyearbyen'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Arctic']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAsiaOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Asia/Tokyo'], ['foo' => 'Timezone:Asia']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'asia/tokyo'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Asia']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAtlanticOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Atlantic/Cape_Verde'], ['foo' => 'Timezone:Atlantic']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'atlantic/cape_verde'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Atlantic']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAustraliaOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Australia/Sydney'], ['foo' => 'Timezone:Australia']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'australia/sydney'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Australia']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithEuropeOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone:Europe']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'europe/kyiv'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Europe']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithIndianOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Indian/Christmas'], ['foo' => 'Timezone:Indian']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'indian/christmas'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Indian']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithPacificOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Pacific/Fiji'], ['foo' => 'Timezone:Pacific']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'pacific/fiji'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Pacific']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithUTCOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:UTC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'utc'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:UTC']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAllOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:All']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:All']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Indian/Christmas'], ['foo' => 'Timezone:All']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone:All']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'indian/christmas'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:All']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithAllWithBCOption()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Indian/Christmas'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'indian/christmas'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:All_with_BC']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:All_with_BC']);
        $this->assertFalse($v->passes());
    }

    public function testValidateTimezoneWithPerCountryOptionWithoutSpecifyingCountry()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'India'], ['foo' => 'Timezone:Per_country,IN']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Cairo'], ['foo' => 'Timezone:Per_country,CA']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'UTC'], ['foo' => 'Timezone:Per_country,GB']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Africa/Windhoek'], ['foo' => 'Timezone:Per_country,NA']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kiev'], ['foo' => 'Timezone:Per_country,UA']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone:Per_country,UA']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'Europe/Kyiv'], ['foo' => 'Timezone:Per_country,ua']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => 'utc'], ['foo' => 'Timezone:Per_country,GB']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone:Per_country,GB']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'GB'], ['foo' => 'Timezone:Per_country,GB']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone:Per_country,GB']);
        $this->assertFalse($v->passes());
    }

    public function testValidateRegex()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asdasdf'], ['x' => 'Regex:/^[a-z]+$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'aasd234fsd1'], ['x' => 'Regex:/^[a-z]+$/i']);
        $this->assertFalse($v->passes());

        // Ensure commas are not interpreted as parameter separators
        $v = new Validator($trans, ['x' => 'a,b'], ['x' => 'Regex:/^a,b$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '12'], ['x' => 'Regex:/^12$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 12], ['x' => 'Regex:/^12$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => ['y' => ['z' => 'james']]], ['x.*.z' => ['Regex:/^(taylor|james)$/i']]);
        $this->assertTrue($v->passes());
    }

    public function testValidateNotRegex()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo bar'], ['x' => 'NotRegex:/[xyz]/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'foo xxx bar'], ['x' => 'NotRegex:/[xyz]/i']);
        $this->assertFalse($v->passes());

        // Ensure commas are not interpreted as parameter separators
        $v = new Validator($trans, ['x' => 'foo bar'], ['x' => 'NotRegex:/x{3,}/i']);
        $this->assertTrue($v->passes());
    }

    public function testValidateDateAndFormat()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => '2000-01-01'], ['x' => 'date']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '01/01/2000'], ['x' => 'date']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '1325376000'], ['x' => 'date']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => 'Not a date'], ['x' => 'date']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => ['Not', 'a', 'date']], ['x' => 'date']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new DateTime], ['x' => 'date']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => new DateTimeImmutable], ['x' => 'date']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '01/01/2001'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '22000-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '00-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => ['Not', 'a', 'date']], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => "Contain null bytes \0"], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        // Set current machine date to 31/xx/xxxx
        $v = new Validator($trans, ['x' => '2013-02'], ['x' => 'date_format:Y-m']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00Atlantic/Azores'], ['x' => 'date_format:Y-m-d\TH:i:se']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00Z'], ['x' => 'date_format:Y-m-d\TH:i:sT']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00+0000'], ['x' => 'date_format:Y-m-d\TH:i:sO']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00+00:30'], ['x' => 'date_format:Y-m-d\TH:i:sP']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01 17:43:59'], ['x' => 'date_format:Y-m-d H:i:s']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01 17:43:59'], ['x' => 'date_format:H:i:s']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2000-01-01 17:43:59'], ['x' => 'date_format:Y-m-d H:i:s,H:i:s']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:43:59'], ['x' => 'date_format:Y-m-d H:i:s,H:i:s']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:43:59'], ['x' => 'date_format:H:i:s']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:43:59'], ['x' => 'date_format:H:i']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:43'], ['x' => 'date_format:H:i']);
        $this->assertTrue($v->passes());
    }

    public function testDateEquals()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => '2000-01-01'], ['x' => 'date_equals:2000-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => new Carbon('2000-01-01')], ['x' => 'date_equals:2000-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => new Carbon('2000-01-01')], ['x' => 'date_equals:2001-01-01']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => new DateTime('2000-01-01'), 'ends' => new DateTime('2000-01-01')], ['ends' => 'date_equals:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'date_equals:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'date_equals:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|date_equals:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|date_equals:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|date_equals:2012-01-01 17:44:00']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|date_equals:2012-01-01 17:43:59']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|date_equals:2012-01-01 17:44:01']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|date_equals:17:44:00']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|date_equals:17:43:59']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|date_equals:17:44:01']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|date_equals:17:44']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|date_equals:17:43']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|date_equals:17:45']);
        $this->assertTrue($v->fails());
    }

    public function testDateEqualsRespectsCarbonTestNowWhenParameterIsRelative()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        Carbon::setTestNow(new Carbon('2018-01-01'));

        $v = new Validator($trans, ['x' => '2018-01-01 00:00:00'], ['x' => 'date_equals:now']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2018-01-01'], ['x' => 'date_equals:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2018-01-01'], ['x' => 'date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2018-01-01'], ['x' => 'date_equals:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '01/01/2018'], ['x' => 'date_format:d/m/Y|date_equals:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '01/01/2018'], ['x' => 'date_format:d/m/Y|date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '01/01/2018'], ['x' => 'date_format:d/m/Y|date_equals:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new DateTime('2018-01-01')], ['x' => 'date_equals:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => new DateTime('2018-01-01')], ['x' => 'date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new DateTime('2018-01-01')], ['x' => 'date_equals:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new Carbon('2018-01-01')], ['x' => 'date_equals:today|after:yesterday|before:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => new Carbon('2018-01-01')], ['x' => 'date_equals:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new Carbon('2018-01-01')], ['x' => 'date_equals:tomorrow']);
        $this->assertTrue($v->fails());
    }

    public function testBeforeAndAfter()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => '2000-01-01'], ['x' => 'Before:2012-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => ['2000-01-01']], ['x' => 'Before:2012-01-01']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => new Carbon('2000-01-01')], ['x' => 'Before:2012-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => [new Carbon('2000-01-01')]], ['x' => 'Before:2012-01-01']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01'], ['x' => 'After:2000-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => ['2012-01-01']], ['x' => 'After:2000-01-01']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => new Carbon('2012-01-01')], ['x' => 'After:2000-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => [new Carbon('2012-01-01')]], ['x' => 'After:2000-01-01']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['start' => '2012-01-01', 'ends' => '2013-01-01'], ['start' => 'After:2000-01-01', 'ends' => 'After:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '2012-01-01', 'ends' => '2000-01-01'], ['start' => 'After:2000-01-01', 'ends' => 'After:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '2012-01-01', 'ends' => '2013-01-01'], ['start' => 'Before:ends', 'ends' => 'After:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '2012-01-01', 'ends' => '2000-01-01'], ['start' => 'Before:ends', 'ends' => 'After:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => new DateTime('2000-01-01')], ['x' => 'Before:2012-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => new DateTime('2012-01-01'), 'ends' => new Carbon('2013-01-01')], ['start' => 'Before:ends', 'ends' => 'After:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '2012-01-01', 'ends' => new DateTime('2013-01-01')], ['start' => 'Before:ends', 'ends' => 'After:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => new DateTime('2012-01-01'), 'ends' => new DateTime('2000-01-01')], ['start' => 'After:2000-01-01', 'ends' => 'After:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => 'today', 'ends' => 'tomorrow'], ['start' => 'Before:ends', 'ends' => 'After:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:43:59'], ['x' => 'Before:2012-01-01 17:44|After:2012-01-01 17:43:58']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:01'], ['x' => 'Before:2012-01-01 17:44:02|After:2012-01-01 17:44']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44'], ['x' => 'Before:2012-01-01 17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44'], ['x' => 'After:2012-01-01 17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:43:59'], ['x' => 'Before:17:44|After:17:43:58']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44:01'], ['x' => 'Before:17:44:02|After:17:44']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'Before:17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'After:17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '0001-01-01T00:00'], ['x' => 'before:1970-01-01']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '0001-01-01T00:00'], ['x' => 'after:1970-01-01']);
        $this->assertTrue($v->fails());
    }

    public function testBeforeAndAfterWithFormat()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => '31/12/2000'], ['x' => 'before:31/02/2012']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => ['31/12/2000']], ['x' => 'before:31/02/2012']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '31/12/2000'], ['x' => 'date_format:d/m/Y|before:31/12/2012']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'after:31/12/2000']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => ['31/12/2012']], ['x' => 'after:31/12/2000']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'date_format:d/m/Y|after:31/12/2000']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2013'], ['start' => 'after:01/01/2000', 'ends' => 'after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2013'], ['start' => 'date_format:d/m/Y|after:31/12/2000', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2000'], ['start' => 'after:31/12/2000', 'ends' => 'after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2000'], ['start' => 'date_format:d/m/Y|after:31/12/2000', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2013'], ['start' => 'before:ends', 'ends' => 'after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2013'], ['start' => 'date_format:d/m/Y|before:ends', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2000'], ['start' => 'before:ends', 'ends' => 'after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => '31/12/2000'], ['start' => 'date_format:d/m/Y|before:ends', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => 'invalid', 'ends' => 'invalid'], ['start' => 'date_format:d/m/Y|before:ends', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => null], ['start' => 'date_format:d/m/Y|before:ends', 'ends' => 'date_format:d/m/Y|after:start']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => null], ['start' => 'date_format:d/m/Y|before:ends', 'ends' => 'nullable|date_format:d/m/Y|after:start']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => null], ['start' => 'before:ends']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['start' => '31/12/2012', 'ends' => null], ['start' => 'before:ends', 'ends' => 'nullable']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after:yesterday|before:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after:today']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|before:today']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'after:yesterday|before:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'after:today']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'before:today']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|before:2012-01-01 17:44:01|after:2012-01-01 17:43:59']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|before:2012-01-01 17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|after:2012-01-01 17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|before:17:44:01|after:17:43:59']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|before:17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|after:17:44:00']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|before:17:45|after:17:43']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|before:17:44']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|after:17:44']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2038-01-18', '2018-05-12' => '2038-01-19'], ['x' => 'date_format:Y-m-d|before:2018-05-12']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '1970-01-02', '2018-05-12' => '1970-01-01'], ['x' => 'date_format:Y-m-d|after:2018-05-12']);
        $this->assertTrue($v->fails());
    }

    public function testWeakBeforeAndAfter()
    {
        date_default_timezone_set('UTC');
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'before_or_equal:2012-01-15']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'before_or_equal:2012-01-16']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'before_or_equal:2012-01-14']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '15/01/2012'], ['x' => 'date_format:d/m/Y|before_or_equal:15/01/2012']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '15/01/2012'], ['x' => 'date_format:d/m/Y|before_or_equal:14/01/2012']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|before_or_equal:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|before_or_equal:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|before_or_equal:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-15']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-14']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-16']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '15/01/2012'], ['x' => 'date_format:d/m/Y|after_or_equal:15/01/2012']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '15/01/2012'], ['x' => 'date_format:d/m/Y|after_or_equal:16/01/2012']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after_or_equal:today']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after_or_equal:yesterday']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after_or_equal:tomorrow']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|before_or_equal:2012-01-01 17:44:00|after_or_equal:2012-01-01 17:44:00']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|before_or_equal:2012-01-01 17:43:59']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-01 17:44:00'], ['x' => 'date_format:Y-m-d H:i:s|after_or_equal:2012-01-01 17:44:01']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|before_or_equal:17:44:00|after_or_equal:17:44:00']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|before_or_equal:17:43:59']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44:00'], ['x' => 'date_format:H:i:s|after_or_equal:17:44:01']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|before_or_equal:17:44|after_or_equal:17:44']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|before_or_equal:17:43']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '17:44'], ['x' => 'date_format:H:i|after_or_equal:17:45']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '2012-01-14', 'bar' => '2012-01-15'], ['foo' => 'before_or_equal:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2012-01-15', 'bar' => '2012-01-15'], ['foo' => 'before_or_equal:bar']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2012-01-15 13:00', 'bar' => '2012-01-15 12:00'], ['foo' => 'before_or_equal:bar']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '2012-01-15 11:00', 'bar' => null], ['foo' => 'date_format:Y-m-d H:i|before_or_equal:bar', 'bar' => 'date_format:Y-m-d H:i']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '2012-01-15 11:00', 'bar' => null], ['foo' => 'date_format:Y-m-d H:i|before_or_equal:bar', 'bar' => 'date_format:Y-m-d H:i|nullable']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2012-01-15 11:00', 'bar' => null], ['foo' => 'before_or_equal:bar']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => '2012-01-15 11:00', 'bar' => null], ['foo' => 'before_or_equal:bar', 'bar' => 'nullable']);
        $this->assertTrue($v->fails());
    }

    public function testSometimesAddingRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', 'Confirmed', function ($i) {
            return $i->x === 'foo';
        });
        $this->assertEquals(['x' => ['Required', 'Confirmed']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => ''], ['y' => 'Required']);
        $v->sometimes('x', 'Required', function ($i) {
            return true;
        });
        $this->assertEquals(['x' => ['Required'], 'y' => ['Required']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', 'Confirmed', function ($i) {
            return $i->x === 'bar';
        });
        $this->assertEquals(['x' => ['Required']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', 'Foo|Bar', function ($i) {
            return $i->x === 'foo';
        });
        $this->assertEquals(['x' => ['Required', 'Foo', 'Bar']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', ['Foo', 'Bar:Baz'], function ($i) {
            return $i->x === 'foo';
        });
        $this->assertEquals(['x' => ['Required', 'Foo', 'Bar:Baz']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [['name' => 'first', 'title' => null]]], []);
        $v->sometimes('foo.*.name', 'Required|String', function ($i) {
            return is_null($i['foo'][0]['title']);
        });
        $this->assertEquals(['foo.0.name' => ['Required', 'String']], $v->getRules());
    }

    public function testItemAwareSometimesAddingRules()
    {
        // ['users'] -> if users is not empty it must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]], ['users.*.name' => 'required|string']);
        $v->sometimes(['users'], 'array', function ($i, $item) {
            return $item !== null;
        });
        $this->assertEquals(['users' => ['array'], 'users.0.name' => ['required', 'string'], 'users.1.name' => ['required', 'string']], $v->getRules());

        // ['users'] -> if users is null no rules will be applied
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['users' => null], ['users.*.name' => 'required|string']);
        $v->sometimes(['users'], 'array', function ($i, $item) {
            return (bool) $item;
        });
        $this->assertEquals([], $v->getRules());

        // ['company.users'] -> if users is not empty it must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]]], ['company.users.*.name' => 'required|string']);
        $v->sometimes(['company.users'], 'array', function ($i, $item) {
            return $item->users !== null;
        });
        $this->assertEquals(['company.users' => ['array'], 'company.users.0.name' => ['required', 'string'], 'company.users.1.name' => ['required', 'string']], $v->getRules());

        // ['company.users'] -> if users is null no rules will be applied
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => null]], ['company' => 'required', 'company.users.*.name' => 'required|string']);
        $v->sometimes(['company.users'], 'array', function ($i, $item) {
            return (bool) $item->users;
        });
        $this->assertEquals(['company' => ['required']], $v->getRules());

        // ['company.*'] -> if users is not empty it must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]]], ['company.users.*.name' => 'required|string']);
        $v->sometimes(['company.*'], 'array', function ($i, $item) {
            return $item !== null;
        });
        $this->assertEquals(['company.users' => ['array'], 'company.users.0.name' => ['required', 'string'], 'company.users.1.name' => ['required', 'string']], $v->getRules());

        // ['company.*'] -> if users is null no rules will be applied
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => null]], ['company' => 'required', 'company.users.*.name' => 'required|string']);
        $v->sometimes(['company.*'], 'array', function ($i, $item) {
            return (bool) $item;
        });
        $this->assertEquals(['company' => ['required']], $v->getRules());

        // ['users.*'] -> all nested array items in users must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]], ['users.*.name' => 'required|string']);
        $v->sometimes(['users.*'], 'array', function ($i, $item) {
            return (bool) $item;
        });
        $this->assertEquals(['users.0' => ['array'], 'users.1' => ['array'], 'users.0.name' => ['required', 'string'], 'users.1.name' => ['required', 'string']], $v->getRules());

        // ['company.users.*'] -> all nested array items in users must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]]], ['company.users.*.name' => 'required|string']);
        $v->sometimes(['company.users.*'], 'array', function () {
            return true;
        });
        $this->assertEquals(['company.users.0' => ['array'], 'company.users.1' => ['array'], 'company.users.0.name' => ['required', 'string'], 'company.users.1.name' => ['required', 'string']], $v->getRules());

        // ['company.*.*'] -> all nested array items in users must be validated as array
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['company' => ['users' => [['name' => 'Taylor'], ['name' => 'Abigail']]]], ['company.users.*.name' => 'required|string']);
        $v->sometimes(['company.*.*'], 'array', function ($i, $item) {
            return true;
        });
        $this->assertEquals(['company.users.0' => ['array'], 'company.users.1' => ['array'], 'company.users.0.name' => ['required', 'string'], 'company.users.1.name' => ['required', 'string']], $v->getRules());

        // ['user.profile.value'] -> multiple true cases, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['user' => ['profile' => ['photo' => 'image.jpg', 'type' => 'email', 'value' => 'test@test.com']]], ['user.profile.*' => ['required']]);
        $v->sometimes(['user.profile.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $v->sometimes('user.profile.photo', 'mimes:jpg,bmp,png', function ($i, $item) {
            return $item->photo;
        });
        $this->assertEquals(['user.profile.value' => ['required', 'email'], 'user.profile.photo' => ['required', 'mimes:jpg,bmp,png'], 'user.profile.type' => ['required']], $v->getRules());

        // ['user.profile.value'] -> multiple true cases with middle wildcard, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['user' => ['profile' => ['photo' => 'image.jpg', 'type' => 'email', 'value' => 'test@test.com']]], ['user.profile.*' => ['required']]);
        $v->sometimes('user.*.value', 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $v->sometimes('user.*.photo', 'mimes:jpg,bmp,png', function ($i, $item) {
            return $item->photo;
        });
        $this->assertEquals(['user.profile.value' => ['required', 'email'], 'user.profile.photo' => ['required', 'mimes:jpg,bmp,png'], 'user.profile.type' => ['required']], $v->getRules());

        // ['profiles.*.value'] -> true and false cases for the same field with middle wildcard, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['profiles' => [['type' => 'email'], ['type' => 'string']]], ['profiles.*.value' => ['required']]);
        $v->sometimes(['profiles.*.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $v->sometimes('profiles.*.value', 'url', function ($i, $item) {
            return $item->type !== 'email';
        });
        $this->assertEquals(['profiles.0.value' => ['required', 'email'], 'profiles.1.value' => ['required', 'url']], $v->getRules());

        // ['profiles.*.value'] -> true case with middle wildcard, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['profiles' => [['type' => 'email'], ['type' => 'string']]], ['profiles.*.value' => ['required']]);
        $v->sometimes(['profiles.*.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $this->assertEquals(['profiles.0.value' => ['required', 'email'], 'profiles.1.value' => ['required']], $v->getRules());

        // ['profiles.*.value'] -> false case with middle wildcard, the item based condition does not match and the optional validation is not added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['profiles' => [['type' => 'string'], ['type' => 'string']]], ['profiles.*.value' => ['required']]);
        $v->sometimes(['profiles.*.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $this->assertEquals(['profiles.0.value' => ['required'], 'profiles.1.value' => ['required']], $v->getRules());

        // ['users.profiles.*.value'] -> true case nested and with middle wildcard, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['users' => ['profiles' => [['type' => 'email'], ['type' => 'string']]]], ['users.profiles.*.value' => ['required']]);
        $v->sometimes(['users.profiles.*.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $this->assertEquals(['users.profiles.0.value' => ['required', 'email'], 'users.profiles.1.value' => ['required']], $v->getRules());

        // ['users.*.*.value'] -> true case nested and with double middle wildcard, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['users' => ['profiles' => [['type' => 'email'], ['type' => 'string']]]], ['users.profiles.*.value' => ['required']]);
        $v->sometimes(['users.*.*.value'], 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $this->assertEquals(['users.profiles.0.value' => ['required', 'email'], 'users.profiles.1.value' => ['required']], $v->getRules());

        // 'user.value' -> true case nested with string, the item based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['user' => ['name' => 'username', 'type' => 'email', 'value' => 'test@test.com']], ['user.*' => ['required']]);
        $v->sometimes('user.value', 'email', function ($i, $item) {
            return $item->type === 'email';
        });
        $this->assertEquals(['user.name' => ['required'], 'user.type' => ['required'], 'user.value' => ['required', 'email']], $v->getRules());

        // 'user.value' -> standard true case with string, the INPUT based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'username', 'type' => 'email', 'value' => 'test@test.com'], ['*' => ['required']]);
        $v->sometimes('value', 'email', function ($i) {
            return $i->type === 'email';
        });
        $this->assertEquals(['name' => ['required'], 'type' => ['required'], 'value' => ['required', 'email']], $v->getRules());

        // ['value'] -> standard true case with array, the INPUT based condition does match and the optional validation is added
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'username', 'type' => 'email', 'value' => 'test@test.com'], ['*' => ['required']]);
        $v->sometimes(['value'], 'email', function ($i, $item) {
            return $i->type === 'email';
        });
        $this->assertEquals(['name' => ['required'], 'type' => ['required'], 'value' => ['required', 'email']], $v->getRules());

        // ['email'] -> if value is set, it will be validated as string
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'test@test.com'], ['*' => ['required']]);
        $v->sometimes(['email'], 'email', function ($i, $item) {
            return $item;
        });
        $this->assertEquals(['email' => ['required', 'email']], $v->getRules());

        // ['attendee.*'] -> if attendee name is set, all other fields will be required as well
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['attendee' => ['name' => 'Taylor', 'title' => 'Creator of Laravel', 'type' => 'Developer']], ['attendee.*' => 'string']);
        $v->sometimes(['attendee.*'], 'required', function ($i, $item) {
            return (bool) $item;
        });
        $this->assertEquals(['attendee.name' => ['string', 'required'], 'attendee.title' => ['string', 'required'], 'attendee.type' => ['string', 'required']], $v->getRules());
    }

    public function testValidateSometimesImplicitEachWithAsterisksBeforeAndAfter()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], []);
        $v->sometimes('foo.*.start', ['before:foo.*.end'], function () {
            return true;
        });
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], []);
        $v->sometimes('foo.*.start', 'before:foo.*.end', function () {
            return true;
        });
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], []);
        $v->sometimes('foo.*.end', ['before:foo.*.start'], function () {
            return true;
        });

        $this->assertTrue($v->fails());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], []);
        $v->sometimes('foo.*.end', ['after:foo.*.start'], function () {
            return true;
        });
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], []);
        $v->sometimes('foo.*.start', ['after:foo.*.end'], function () {
            return true;
        });
        $this->assertTrue($v->fails());
    }

    public function testCustomValidators()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo']);
        $v->addExtension('foo', function () {
            return false;
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo_bar' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtension('FooBar', function () {
            return false;
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtension('FooBar', function () {
            return false;
        });
        $v->setFallbackMessages(['foo_bar' => 'foo!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtensions([
            'FooBar' => function () {
                return false;
            },
        ]);
        $v->setFallbackMessages(['foo_bar' => 'foo!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));
    }

    public function testClassBasedCustomValidators()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo']);
        $v->setContainer($container = m::mock(Container::class));
        $v->addExtension('foo', 'Foo@bar');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock(stdClass::class));
        $foo->shouldReceive('bar')->once()->andReturn(false);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));
    }

    public function testClassBasedCustomValidatorsUsingConventionalMethod()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo']);
        $v->setContainer($container = m::mock(Container::class));
        $v->addExtension('foo', 'Foo');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock(stdClass::class));
        $foo->shouldReceive('validate')->once()->andReturn(false);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertSame('foo!', $v->messages()->first('name'));
    }

    public function testCustomImplicitValidators()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['implicit_rule' => 'foo']);
        $v->addImplicitExtension('implicit_rule', function () {
            return true;
        });
        $this->assertTrue($v->passes());
    }

    public function testCustomDependentValidators()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans,
            [
                ['name' => 'Jamie', 'age' => 27],
            ],
            ['*.name' => 'dependent_rule:*.age']
        );
        $v->addDependentExtension('dependent_rule', function ($name) use ($v) {
            return Arr::get($v->getData(), $name) === 'Jamie';
        });
        $this->assertTrue($v->passes());
    }

    public function testExceptionThrownOnIncorrectParameterCount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule required_if requires at least 2 parameters.');

        $trans = $this->getTranslator();
        $v = new Validator($trans, [], ['foo' => 'required_if:foo']);
        $v->passes();
    }

    public function testValidateImplicitEachWithAsterisks()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $data = ['foo' => [5, 10, 15]];

        // pipe rules fails
        $v = new Validator($trans, $data, [
            'foo' => 'Array',
            'foo.*' => 'Numeric|Min:6|Max:16',
        ]);
        $this->assertFalse($v->passes());

        // pipe passes
        $v = new Validator($trans, $data, [
            'foo' => 'Array',
            'foo.*' => 'Numeric|Min:4|Max:16',
        ]);
        $this->assertTrue($v->passes());

        // array rules fails
        $v = new Validator($trans, $data, [
            'foo' => 'Array',
            'foo.*' => ['Numeric', 'Min:6', 'Max:16'],
        ]);
        $this->assertFalse($v->passes());

        // array rules passes
        $v = new Validator($trans, $data, [
            'foo' => 'Array',
            'foo.*' => ['Numeric', 'Min:4', 'Max:16'],
        ]);
        $this->assertTrue($v->passes());

        // string passes
        $v = new Validator($trans,
            ['foo' => [['name' => 'first'], ['name' => 'second']]],
            ['foo' => 'Array', 'foo.*.name' => 'Required|String']);
        $this->assertTrue($v->passes());

        // numeric fails
        $v = new Validator($trans,
            ['foo' => [['name' => 'first'], ['name' => 'second']]],
            ['foo' => 'Array', 'foo.*.name' => 'Required|Numeric']);
        $this->assertFalse($v->passes());

        // nested array fails
        $v = new Validator($trans,
            ['foo' => [['name' => 'first', 'votes' => [1, 2]], ['name' => 'second', 'votes' => ['something', 2]]]],
            ['foo' => 'Array', 'foo.*.name' => 'Required|String', 'foo.*.votes.*' => 'Required|Integer']);
        $this->assertFalse($v->passes());

        // multiple items passes
        $v = new Validator($trans, ['foo' => [['name' => 'first'], ['name' => 'second']]],
            ['foo' => 'Array', 'foo.*.name' => ['Required', 'String']]);
        $this->assertTrue($v->passes());

        // multiple items fails
        $v = new Validator($trans, ['foo' => [['name' => 'first'], ['name' => 'second']]],
            ['foo' => 'Array', 'foo.*.name' => ['Required', 'Numeric']]);
        $this->assertFalse($v->passes());

        // nested arrays fails
        $v = new Validator($trans,
            ['foo' => [['name' => 'first', 'votes' => [1, 2]], ['name' => 'second', 'votes' => ['something', 2]]]],
            ['foo' => 'Array', 'foo.*.name' => ['Required', 'String'], 'foo.*.votes.*' => ['Required', 'Integer']]);
        $this->assertFalse($v->passes());
    }

    public function testSometimesOnArraysInImplicitRules()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [['bar' => 'baz']], ['*.foo' => 'sometimes|required|string']);
        $this->assertTrue($v->passes());

        // $data = ['names' => [['second' => []]]];
        // $v = new Validator($trans, $data, ['names.*.second' => 'sometimes|required']);
        // $this->assertFalse($v->passes());

        $data = ['names' => [['second' => ['Taylor']]]];
        $v = new Validator($trans, $data, ['names.*.second' => 'sometimes|required|string']);
        $this->assertFalse($v->passes());
        $this->assertEquals(['validation.string'], $v->errors()->get('names.0.second'));
    }

    public function testValidateImplicitEachWithAsterisksForRequiredNonExistingKey()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = ['companies' => ['spark']];
        $v = new Validator($trans, $data, ['companies.*.name' => 'required']);
        $this->assertFalse($v->passes());

        $data = ['names' => [['second' => 'I have no first']]];
        $v = new Validator($trans, $data, ['names.*.first' => 'required']);
        $this->assertFalse($v->passes());

        $data = [];
        $v = new Validator($trans, $data, ['names.*.first' => 'required']);
        $this->assertTrue($v->passes());

        $data = ['names' => [['second' => 'I have no first']]];
        $v = new Validator($trans, $data, ['names.*.first' => 'required']);
        $this->assertFalse($v->passes());

        $data = [
            'people' => [
                ['cars' => [['model' => 2005], []]],
            ],
        ];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());

        $data = [
            'people' => [
                ['name' => 'test', 'cars' => [['model' => 2005], ['name' => 'test2']]],
            ],
        ];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());

        $data = [
            'people' => [
                ['phones' => ['iphone', 'android'], 'cars' => [['model' => 2005], ['name' => 'test2']]],
            ],
        ];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());

        $data = ['names' => [['second' => '2']]];
        $v = new Validator($trans, $data, ['names.*.first' => 'sometimes|required']);
        $this->assertTrue($v->passes());

        $data = [
            'people' => [
                ['name' => 'Jon', 'email' => 'a@b.c'],
                ['name' => 'Jon'],
            ],
        ];
        $v = new Validator($trans, $data, ['people.*.email' => 'required']);
        $this->assertFalse($v->passes());

        $data = [
            'people' => [
                [
                    'name' => 'Jon',
                    'cars' => [
                        ['model' => 2014],
                    ],
                ],
                [
                    'name' => 'Arya',
                    'cars' => [
                        ['name' => 'test'],
                    ],
                ],
            ],
        ];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());
    }

    public function testParsingArrayKeysWithDot()
    {
        $trans = $this->getIlluminateArrayTranslator();
        // Interpreted dot fails on empty value
        $v = new Validator($trans, ['foo' => ['bar' => ''], 'foo.bar' => 'valid'], ['foo.bar' => 'required']);
        $this->assertTrue($v->fails());
        // Escaped dot fails on empty value
        $v = new Validator($trans, ['foo' => ['bar' => 'valid'], 'foo.bar' => ''], ['foo\.bar' => 'required']);
        $this->assertTrue($v->fails());
        // Interpreted dot succeeds
        $v = new Validator($trans, ['foo' => ['bar' => 'valid'], 'foo.bar' => 'zxc'], ['foo\.bar' => 'required']);
        $this->assertFalse($v->fails());
        // Interpreted dot followed by escaped dot fails on empty value
        $v = new Validator($trans, ['foo' => ['bar.baz' => '']], ['foo.bar\.baz' => 'required']);
        $this->assertTrue($v->fails());
        // Interpreted dot followed by escaped dot fails on empty value
        $v = new Validator($trans, ['foo' => [['bar.baz' => ''], ['bar.baz' => '']]], ['foo.*.bar\.baz' => 'required']);
        $this->assertTrue($v->fails());
    }

    public function testParsingArrayKeysWithDotWhenTestingExistence()
    {
        $trans = $this->getIlluminateArrayTranslator();
        // RequiredWith using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => '', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'required_with:bar.foo\.bar']);
        $this->assertFalse($v->passes());
        // RequiredWithAll using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => '', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'required_with_all:bar.foo\.bar']);
        $this->assertFalse($v->passes());
        // RequiredWithout using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => 'valid', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'required_without:bar.foo\.bar']);
        $this->assertTrue($v->passes());
        // RequiredWithoutAll using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => 'valid', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'required_without_all:bar.foo\.bar']);
        $this->assertTrue($v->passes());
        // Same using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => 'valid', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'same:bar.foo\.bar']);
        $this->assertTrue($v->passes());
        // RequiredUnless using escaped dot in a nested array
        $v = new Validator($trans, ['foo' => '', 'bar' => ['foo.bar' => 'valid']], ['foo' => 'required_unless:bar.foo\.bar,valid']);
        $this->assertTrue($v->passes());
    }

    public function testPassingSlashVulnerability()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'matrix' => ['\\' => ['invalid'], '1\\' => ['invalid']],
        ], [
            'matrix.*.*' => 'integer',
        ]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, [
            'matrix' => ['\\' => [1], '1\\' => [1]],
        ], [
            'matrix.*.*' => 'integer',
        ]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => ['bar' => 'valid'], 'foo.bar' => 'invalid', 'foo->bar' => 'valid',
        ], [
            'foo\.bar' => 'required|in:valid',
        ]);
        $this->assertTrue($v->fails());
    }

    public function testPlaceholdersAreReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'matrix' => ['\\' => ['invalid'], '1\\' => ['invalid']],
        ], [
            'matrix.*.*' => 'integer',
        ]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, [
            'matrix' => ['\\' => [1], '1\\' => [1]],
        ], [
            'matrix.*.*' => 'integer',
        ]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => ['bar' => 'valid'], 'foo.bar' => 'invalid', 'foo->bar' => 'valid',
        ], [
            'foo\.bar' => 'required|in:valid',
        ]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('foo.bar', $v->errors()->getMessages());

        $v = new Validator($trans, [
            'foo.bar' => 'valid',
        ], [
            'foo\.bar' => 'required|in:valid',
        ]);
        $this->assertTrue($v->passes());
        $this->assertArrayHasKey('foo.bar', $v->validated());
    }

    public function testCoveringEmptyKeys()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['' => ['bar' => '']]], ['foo.*.bar' => 'required']);
        $this->assertTrue($v->fails());
    }

    public function testImplicitEachWithAsterisksWithArrayValues()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['foo' => ['bar.baz' => '']], ['foo' => 'required']);
        $this->assertEquals(['foo' => ['bar.baz' => '']], $v->validated());
    }

    public function testValidateNestedArrayWithCommonParentChildKey()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = [
            'products' => [
                [
                    'price' => 2,
                    'options' => [
                        ['price' => 1],
                    ],
                ],
                [
                    'price' => 2,
                    'options' => [
                        ['price' => 0],
                    ],
                ],
            ],
        ];
        $v = new Validator($trans, $data, ['products.*.price' => 'numeric|min:1']);
        $this->assertTrue($v->passes());
    }

    public function testValidateNestedArrayWithNonNumericKeys()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = [
            'item_amounts' => [
                'item_123' => 2,
            ],
        ];

        $v = new Validator($trans, $data, ['item_amounts.*' => 'numeric|min:5']);
        $this->assertFalse($v->passes());
    }

    public function testValidateImplicitEachWithAsterisksConfirmed()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // confirmed passes
        $v = new Validator($trans, [
            'foo' => [
                ['password' => 'foo0', 'password_confirmation' => 'foo0'],
                ['password' => 'foo1', 'password_confirmation' => 'foo1'],
            ],
        ], ['foo.*.password' => 'confirmed']);
        $this->assertTrue($v->passes());

        // nested confirmed passes
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['password' => 'bar0', 'password_confirmation' => 'bar0'],
                        ['password' => 'bar1', 'password_confirmation' => 'bar1'],
                    ],
                ],
                [
                    'bar' => [
                        ['password' => 'bar2', 'password_confirmation' => 'bar2'],
                        ['password' => 'bar3', 'password_confirmation' => 'bar3'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.password' => 'confirmed']);
        $this->assertTrue($v->passes());

        // confirmed fails
        $v = new Validator($trans, [
            'foo' => [
                ['password' => 'foo0', 'password_confirmation' => 'bar0'],
                ['password' => 'foo1'],
            ],
        ], ['foo.*.password' => 'confirmed']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.password'));
        $this->assertTrue($v->messages()->has('foo.1.password'));

        // nested confirmed fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['password' => 'bar0'],
                        ['password' => 'bar1', 'password_confirmation' => 'bar2'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.password' => 'confirmed']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.password'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.password'));
    }

    public function testValidateImplicitEachWithAsterisksDifferent()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // different passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'foo', 'last' => 'bar'],
                ['name' => 'bar', 'last' => 'foo'],
            ],
        ], ['foo.*.name' => ['different:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested different passes
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => 'foo', 'last' => 'bar'],
                        ['name' => 'bar', 'last' => 'foo'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['different:foo.*.bar.*.last']]);
        $this->assertTrue($v->passes());

        // different fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'foo', 'last' => 'foo'],
                ['name' => 'bar', 'last' => 'bar'],
            ],
        ], ['foo.*.name' => ['different:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested different fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => 'foo', 'last' => 'foo'],
                        ['name' => 'bar', 'last' => 'bar'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['different:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksSame()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // same passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'foo', 'last' => 'foo'],
                ['name' => 'bar', 'last' => 'bar'],
            ],
        ], ['foo.*.name' => ['same:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested same passes
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => 'foo', 'last' => 'foo'],
                        ['name' => 'bar', 'last' => 'bar'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['same:foo.*.bar.*.last']]);
        $this->assertTrue($v->passes());

        // same fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'foo', 'last' => 'bar'],
                ['name' => 'bar', 'last' => 'foo'],
            ],
        ], ['foo.*.name' => ['same:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested same fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => 'foo', 'last' => 'bar'],
                        ['name' => 'bar', 'last' => 'foo'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['same:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequired()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first'],
                ['name' => 'second'],
            ],
        ], ['foo.*.name' => ['Required']]);
        $this->assertTrue($v->passes());

        // nested required passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first'],
                ['name' => 'second'],
            ],
        ], ['foo.*.name' => ['Required']]);
        $this->assertTrue($v->passes());

        // required fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null],
                ['name' => null, 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null],
                        ['name' => null],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredIf()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_if passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'foo'],
                ['last' => 'bar'],
            ],
        ], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // nested required_if passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'foo'],
                ['last' => 'bar'],
            ],
        ], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // required_if fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'foo'],
                ['name' => null, 'last' => 'foo'],
            ],
        ], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_if fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'last' => 'foo'],
                        ['name' => null, 'last' => 'foo'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_if:foo.*.bar.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_unless passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'foo'],
                ['name' => 'second', 'last' => 'bar'],
            ],
        ], ['foo.*.name' => ['Required_unless:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // nested required_unless passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'foo'],
                ['name' => 'second', 'last' => 'foo'],
            ],
        ], ['foo.*.bar.*.name' => ['Required_unless:foo.*.bar.*.last,foo']]);
        $this->assertTrue($v->passes());

        // required_unless fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'baz'],
                ['name' => null, 'last' => 'bar'],
            ],
        ], ['foo.*.name' => ['Required_unless:foo.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_unless fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'last' => 'bar'],
                        ['name' => null, 'last' => 'bar'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_unless:foo.*.bar.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWith()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_with passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'last'],
                ['name' => 'second', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested required_with passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'last'],
                ['name' => 'second', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertTrue($v->passes());

        // required_with fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'last'],
                ['name' => null, 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        $v = new Validator($trans, [
            'fields' => [
                'fr' => ['name' => '', 'content' => 'ragnar'],
                'es' => ['name' => '', 'content' => 'lagertha'],
            ],
        ], ['fields.*.name' => 'required_with:fields.*.content']);
        $this->assertFalse($v->passes());

        // nested required_with fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'last' => 'last'],
                        ['name' => null, 'last' => 'last'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_with:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithAll()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_with_all passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'last', 'middle' => 'middle'],
                ['name' => 'second', 'last' => 'last', 'middle' => 'middle'],
            ],
        ], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // nested required_with_all passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'last' => 'last', 'middle' => 'middle'],
                ['name' => 'second', 'last' => 'last', 'middle' => 'middle'],
            ],
        ], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_with_all fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'last', 'middle' => 'middle'],
                ['name' => null, 'last' => 'last', 'middle' => 'middle'],
            ],
        ], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_with_all fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'last' => 'last', 'middle' => 'middle'],
                        ['name' => null, 'last' => 'last', 'middle' => 'middle'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_with_all:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithout()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_without passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'middle' => 'middle'],
                ['name' => 'second', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // nested required_without passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first', 'middle' => 'middle'],
                ['name' => 'second', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_without fails
        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'last' => 'last'],
                ['name' => null, 'middle' => 'middle'],
            ],
        ], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_without fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'last' => 'last'],
                        ['name' => null, 'middle' => 'middle'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_without:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithoutAll()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_without_all passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first'],
                ['name' => null, 'middle' => 'middle'],
                ['name' => null, 'middle' => 'middle', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_without_all fails
        // nested required_without_all passes
        $v = new Validator($trans, [
            'foo' => [
                ['name' => 'first'],
                ['name' => null, 'middle' => 'middle'],
                ['name' => null, 'middle' => 'middle', 'last' => 'last'],
            ],
        ], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
                ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
            ],
        ], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_without_all fails
        $v = new Validator($trans, [
            'foo' => [
                [
                    'bar' => [
                        ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
                        ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
                    ],
                ],
            ],
        ], ['foo.*.bar.*.name' => ['Required_without_all:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksBeforeAndAfter()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], ['foo.*.start' => ['before:foo.*.end']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], ['foo.*.end' => ['before:foo.*.start']]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], ['foo.*.end' => ['after:foo.*.start']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, [
            'foo' => [
                ['start' => '2016-04-19', 'end' => '2017-04-19'],
            ],
        ], ['foo.*.start' => ['after:foo.*.end']]);
        $this->assertTrue($v->fails());
    }

    public function testGetLeadingExplicitAttributePath()
    {
        $this->assertNull(ValidationData::getLeadingExplicitAttributePath('*.email'));
        $this->assertSame('foo', ValidationData::getLeadingExplicitAttributePath('foo.*'));
        $this->assertSame('foo.bar', ValidationData::getLeadingExplicitAttributePath('foo.bar.*.baz'));
        $this->assertSame('foo.bar.1', ValidationData::getLeadingExplicitAttributePath('foo.bar.1'));
    }

    public function testExtractDataFromPath()
    {
        $data = [['email' => 'mail'], ['email' => 'mail2']];
        $this->assertEquals([['email' => 'mail'], ['email' => 'mail2']], ValidationData::extractDataFromPath(null, $data));

        $data = ['cat' => ['cat1' => ['name']], ['cat2' => ['name2']]];
        $this->assertEquals(['cat' => ['cat1' => ['name']]], ValidationData::extractDataFromPath('cat.cat1', $data));

        $data = ['cat' => ['cat1' => ['name' => '1', 'price' => 1]], ['cat2' => ['name' => 2]]];
        $this->assertEquals(['cat' => ['cat1' => ['name' => '1']]], ValidationData::extractDataFromPath('cat.cat1.name', $data));
    }

    public function testParsingTablesFromModels()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], []);

        $implicit_no_connection = $v->parseTable(ImplicitTableModel::class);
        $this->assertNull($implicit_no_connection[0]);
        $this->assertSame('implicit_table_models', $implicit_no_connection[1]);

        $explicit_no_connection = $v->parseTable(ExplicitTableModel::class);
        $this->assertNull($explicit_no_connection[0]);
        $this->assertSame('explicits', $explicit_no_connection[1]);

        $explicit_model_with_prefix = $v->parseTable(ExplicitPrefixedTableModel::class);
        $this->assertNull($explicit_model_with_prefix[0]);
        $this->assertSame('prefix.explicits', $explicit_model_with_prefix[1]);

        $explicit_table_with_connection_prefix = $v->parseTable('connection.table');
        $this->assertSame('connection', $explicit_table_with_connection_prefix[0]);
        $this->assertSame('table', $explicit_table_with_connection_prefix[1]);

        $noneloquent_no_connection = $v->parseTable(NonEloquentModel::class);
        $this->assertNull($noneloquent_no_connection[0]);
        $this->assertEquals(NonEloquentModel::class, $noneloquent_no_connection[1]);

        $raw_no_connection = $v->parseTable('table');
        $this->assertNull($raw_no_connection[0]);
        $this->assertSame('table', $raw_no_connection[1]);

        $implicit_connection = $v->parseTable('connection.'.ImplicitTableModel::class);
        $this->assertSame('connection', $implicit_connection[0]);
        $this->assertSame('implicit_table_models', $implicit_connection[1]);

        $explicit_connection = $v->parseTable('connection.'.ExplicitTableModel::class);
        $this->assertSame('connection', $explicit_connection[0]);
        $this->assertSame('explicits', $explicit_connection[1]);

        $explicit_model_implicit_connection = $v->parseTable(ExplicitTableAndConnectionModel::class);
        $this->assertSame('connection', $explicit_model_implicit_connection[0]);
        $this->assertSame('explicits', $explicit_model_implicit_connection[1]);

        $noneloquent_connection = $v->parseTable('connection.'.NonEloquentModel::class);
        $this->assertSame('connection', $noneloquent_connection[0]);
        $this->assertEquals(NonEloquentModel::class, $noneloquent_connection[1]);

        $raw_connection = $v->parseTable('connection.table');
        $this->assertSame('connection', $raw_connection[0]);
        $this->assertSame('table', $raw_connection[1]);
    }

    public function testUsingSettersWithImplicitRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['a', 'b', 'c']], ['foo.*' => 'string']);
        $v->setData(['foo' => ['a', 'b', 'c', 4]]);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['a', 'b', 'c']], ['foo.*' => 'string']);
        $v->setRules(['foo.*' => 'integer']);
        $this->assertFalse($v->passes());
    }

    public function testInvalidMethod()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans,
            [
                ['name' => 'John'],
                ['name' => null],
                ['name' => ''],
            ],
            [
                '*.name' => 'required',
            ]);

        $this->assertEquals(
            [
                1 => ['name' => null],
                2 => ['name' => ''],
            ],
            $v->invalid()
        );

        $v = new Validator($trans,
            [
                'name' => '',
            ],
            [
                'name' => 'required',
            ]);

        $this->assertEquals(
            [
                'name' => '',
            ],
            $v->invalid()
        );
    }

    public function testValidMethod()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans,
            [
                ['name' => 'John'],
                ['name' => null],
                ['name' => ''],
                ['name' => 'Doe'],
            ],
            [
                '*.name' => 'required',
            ]);

        $this->assertEquals(
            [
                0 => ['name' => 'John'],
                3 => ['name' => 'Doe'],
            ],
            $v->valid()
        );

        $v = new Validator($trans,
            [
                'name' => 'Carlos',
                'age' => 'unknown',
                'gender' => 'male',
            ],
            [
                'name' => 'required',
                'gender' => 'in:male,female',
                'age' => 'required|int',
            ]);

        $this->assertEquals(
            [
                'name' => 'Carlos',
                'gender' => 'male',
            ],
            $v->valid()
        );
    }

    public function testNestedInvalidMethod()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [
            'testvalid' => 'filled',
            'testinvalid' => '',
            'records' => [
                'ABC123',
                'ABC122',
                'ABB132',
                'ADCD23',
            ],
        ], [
            'testvalid' => 'filled',
            'testinvalid' => 'filled',
            'records.*' => [
                'required',
                'regex:/[A-F]{3}[0-9]{3}/',
            ],
        ]);
        $this->assertEquals(
            [
                'testinvalid' => '',
                'records' => [
                    3 => 'ADCD23',
                ],
            ],
            $v->invalid()
        );
    }

    public function testMultipleFileUploads()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $file = new File(__FILE__, false);
        $file2 = new File(__FILE__, false);
        $v = new Validator($trans, ['file' => [$file, $file2]], ['file.*' => 'Required|mimes:xls']);
        $this->assertFalse($v->passes());
    }

    public function testFileUploads()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $file = new File(__FILE__, false);
        $v = new Validator($trans, ['file' => $file], ['file' => 'Required|mimes:xls']);
        $this->assertFalse($v->passes());
    }

    public function testCustomValidationObject()
    {
        // Test passing case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 'taylor'],
            [
                'name' => new class implements Rule
                {
                    public function passes($attribute, $value)
                    {
                        return $value === 'taylor';
                    }

                    public function message()
                    {
                        return ':attribute must be taylor';
                    }
                },
            ]
        );

        $this->assertTrue($v->passes());

        // Test failing case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 'adam'],
            [
                'name' => [
                    new class implements Rule
                    {
                        public function passes($attribute, $value)
                        {
                            return $value === 'taylor';
                        }

                        public function message()
                        {
                            return ':attribute must be taylor';
                        }
                    },
                ],
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('name must be taylor', $v->errors()->all()[0]);

        // Test passing case with Closure...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 'taylor'],
            [
                'name.*' => function ($attribute, $value, $fail) {
                    if ($value !== 'taylor') {
                        $fail(':attribute was '.$value.' instead of taylor');
                    }
                },
            ]
        );

        $this->assertTrue($v->passes());

        // Test failing case with Closure...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 'adam'],
            [
                'name' => function ($attribute, $value, $fail) {
                    if ($value !== 'taylor') {
                        $fail(':attribute was '.$value.' instead of taylor');
                    }
                },
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('name was adam instead of taylor', $v->errors()->all()[0]);

        // Test complex failing case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 'taylor', 'states' => ['AR', 'TX'], 'number' => 9],
            [
                'states.*' => new class implements Rule
                {
                    public function passes($attribute, $value)
                    {
                        return in_array($value, ['AK', 'HI']);
                    }

                    public function message()
                    {
                        return ':attribute must be AR or TX';
                    }
                },
                'name' => function ($attribute, $value, $fail) {
                    if ($value !== 'taylor') {
                        $fail(':attribute must be taylor');
                    }
                },
                'number' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if ($value % 4 !== 0) {
                            $fail(':attribute must be divisible by 4');
                        }
                    },
                ],
            ]
        );

        $this->assertFalse($v->passes());
        $this->assertSame('states.0 must be AR or TX', $v->errors()->get('states.0')[0]);
        $this->assertSame('states.1 must be AR or TX', $v->errors()->get('states.1')[0]);
        $this->assertSame('number must be divisible by 4', $v->errors()->get('number')[0]);

        // Test array of messages with failing case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 42],
            [
                'name' => new class implements Rule
                {
                    public function passes($attribute, $value)
                    {
                        return $value === 'taylor';
                    }

                    public function message()
                    {
                        return [':attribute must be taylor', ':attribute must be a first name'];
                    }
                },
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('name must be taylor', $v->errors()->get('name')[0]);
        $this->assertSame('name must be a first name', $v->errors()->get('name')[1]);

        // Test array of messages with multiple rules for one attribute case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => 42],
            [
                'name' => [
                    new class implements Rule
                    {
                        public function passes($attribute, $value)
                        {
                            return $value === 'taylor';
                        }

                        public function message()
                        {
                            return [':attribute must be taylor', ':attribute must be a first name'];
                        }
                    }, 'string',
                ],
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('name must be taylor', $v->errors()->get('name')[0]);
        $this->assertSame('name must be a first name', $v->errors()->get('name')[1]);
        $this->assertSame('validation.string', $v->errors()->get('name')[2]);

        // Test access to the validator data
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['password' => 'foo', 'password_confirmation' => 'foo'],
            [
                'password' => [
                    new class implements Rule, DataAwareRule
                    {
                        protected $data;

                        public function setData($data)
                        {
                            $this->data = $data;
                        }

                        public function passes($attribute, $value)
                        {
                            return $value === $this->data['password_confirmation'];
                        }

                        public function message()
                        {
                            return ['The :attribute confirmation does not match.'];
                        }
                    }, 'string',
                ],
            ]
        );

        $this->assertTrue($v->passes());

        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['password' => 'foo', 'password_confirmation' => 'bar'],
            [
                'password' => [
                    new class implements Rule, DataAwareRule
                    {
                        protected $data;

                        public function setData($data)
                        {
                            $this->data = $data;
                        }

                        public function passes($attribute, $value)
                        {
                            return $value === $this->data['password_confirmation'];
                        }

                        public function message()
                        {
                            return ['The :attribute confirmation does not match.'];
                        }
                    }, 'string',
                ],
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('The password confirmation does not match.', $v->errors()->get('password')[0]);

        // Test access to the validator
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['base' => 21, 'double' => 42],
            [
                'base' => ['integer'],
                'double' => [
                    'integer',
                    new class implements Rule, ValidatorAwareRule
                    {
                        protected $validator;

                        public function setValidator($validator)
                        {
                            $this->validator = $validator;
                        }

                        public function passes($attribute, $value)
                        {
                            if ($this->validator->errors()->hasAny(['base', $attribute])) {
                                return true;
                            }

                            return $value === 2 * $this->validator->getData()['base'];
                        }

                        public function message()
                        {
                            return ['The :attribute must be the double of base.'];
                        }
                    },
                ],
            ]
        );

        $this->assertTrue($v->passes());

        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['base' => 21, 'double' => 10],
            [
                'base' => ['integer'],
                'double' => [
                    'integer',
                    new class implements Rule, ValidatorAwareRule
                    {
                        protected $validator;

                        public function setValidator($validator)
                        {
                            $this->validator = $validator;
                        }

                        public function passes($attribute, $value)
                        {
                            if ($this->validator->errors()->hasAny(['base', $attribute])) {
                                return true;
                            }

                            return $value === 2 * $this->validator->getData()['base'];
                        }

                        public function message()
                        {
                            return ['The :attribute must be the double of base.'];
                        }
                    },
                ],
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertSame('The double must be the double of base.', $v->errors()->get('double')[0]);

        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['base' => 21, 'double' => 'foo'],
            [
                'base' => ['integer'],
                'double' => [
                    'integer',
                    new class implements Rule, ValidatorAwareRule
                    {
                        protected $validator;

                        public function setValidator($validator)
                        {
                            $this->validator = $validator;
                        }

                        public function passes($attribute, $value)
                        {
                            if ($this->validator->errors()->hasAny(['base', $attribute])) {
                                return true;
                            }

                            return $value === 2 * $this->validator->getData()['base'];
                        }

                        public function message()
                        {
                            return ['The :attribute must be the double of base.'];
                        }
                    },
                ],
            ]
        );

        $this->assertTrue($v->fails());
        $this->assertCount(1, $v->errors()->get('double'));
        $this->assertSame('validation.integer', $v->errors()->get('double')[0]);
    }

    public function testCustomValidationObjectWithDotKeysIsCorrectlyPassedValue()
    {
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['foo' => ['foo.bar' => 'baz']],
            [
                'foo' => new class implements Rule
                {
                    public function passes($attribute, $value)
                    {
                        return $value === ['foo.bar' => 'baz'];
                    }

                    public function message()
                    {
                        return ':attribute must be baz';
                    }
                },
            ]
        );

        $this->assertTrue($v->passes());

        // Test failed attributes contains proper entries
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['foo' => ['foo.bar' => 'baz']],
            [
                'foo.foo\.bar' => new class implements Rule
                {
                    public function passes($attribute, $value)
                    {
                        return false;
                    }

                    public function message()
                    {
                        return ':attribute must be baz';
                    }
                },
            ]
        );

        $this->assertFalse($v->passes());
        $this->assertIsArray($v->failed()['foo.foo.bar']);
    }

    public function testImplicitCustomValidationObjects()
    {
        // Test passing case...
        $v = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['name' => ''],
            [
                'name' => $rule = new class implements ImplicitRule
                {
                    public $called = false;

                    public function passes($attribute, $value)
                    {
                        $this->called = true;

                        return true;
                    }

                    public function message()
                    {
                        return 'message';
                    }
                },
            ]
        );

        $this->assertTrue($v->passes());
        $this->assertTrue($rule->called);
    }

    public function testValidateReturnsValidatedData()
    {
        $post = ['first' => 'john', 'preferred' => 'john', 'last' => 'doe', 'type' => 'admin'];

        $v = new Validator($this->getIlluminateArrayTranslator(), $post, ['first' => 'required', 'preferred' => 'required']);
        $v->sometimes('type', 'required', function () {
            return false;
        });
        $data = $v->validate();

        $this->assertEquals(['first' => 'john', 'preferred' => 'john'], $data);
    }

    public function testValidateReturnsValidatedDataNestedRules()
    {
        $post = ['nested' => ['foo' => 'bar', 'baz' => ''], 'array' => [1, 2]];

        $rules = ['nested.foo' => 'required', 'array.*' => 'integer'];

        $v = new Validator($this->getIlluminateArrayTranslator(), $post, $rules);
        $v->sometimes('type', 'required', function () {
            return false;
        });
        $data = $v->validate();

        $this->assertEquals(['nested' => ['foo' => 'bar'], 'array' => [1, 2]], $data);
    }

    public function testValidateReturnsValidatedDataNestedChildRules()
    {
        $post = ['nested' => ['foo' => 'bar', 'with' => 'extras', 'type' => 'admin']];

        $v = new Validator($this->getIlluminateArrayTranslator(), $post, ['nested.foo' => 'required']);
        $v->sometimes('nested.type', 'required', function () {
            return false;
        });
        $data = $v->validate();

        $this->assertEquals(['nested' => ['foo' => 'bar']], $data);
    }

    public function testValidateReturnsValidatedDataNestedArrayRules()
    {
        $post = ['nested' => [['bar' => 'baz', 'with' => 'extras', 'type' => 'admin'], ['bar' => 'baz2', 'with' => 'extras', 'type' => 'admin']]];

        $v = new Validator($this->getIlluminateArrayTranslator(), $post, ['nested.*.bar' => 'required']);
        $v->sometimes('nested.*.type', 'required', function () {
            return false;
        });
        $data = $v->validate();

        $this->assertEquals(['nested' => [['bar' => 'baz'], ['bar' => 'baz2']]], $data);
    }

    public function testValidateAndValidatedData()
    {
        $post = ['first' => 'john', 'preferred' => 'john', 'last' => 'doe', 'type' => 'admin'];

        $v = new Validator($this->getIlluminateArrayTranslator(), $post, ['first' => 'required', 'preferred' => 'required']);
        $v->sometimes('type', 'required', function () {
            return false;
        });
        $data = $v->validate();
        $validatedData = $v->validated();

        $this->assertEquals(['first' => 'john', 'preferred' => 'john'], $data);
        $this->assertEquals($data, $validatedData);
    }

    public function testValidatedNotValidateTwiceData()
    {
        $post = ['first' => 'john', 'preferred' => 'john', 'last' => 'doe', 'type' => 'admin'];

        $validateCount = 0;
        $v = new Validator($this->getIlluminateArrayTranslator(), $post, ['first' => 'required', 'preferred' => 'required']);
        $v->after(function () use (&$validateCount) {
            $validateCount++;
        });
        $data = $v->validate();
        $v->validated();

        $this->assertEquals(['first' => 'john', 'preferred' => 'john'], $data);
        $this->assertEquals(1, $validateCount);
    }

    public function testMultiplePassesCalls()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [], ['foo' => 'string|required']);
        $this->assertFalse($v->passes());
        $this->assertFalse($v->passes());
    }

    /**
     * @dataProvider validUuidList
     */
    public function testValidateWithValidUuid($uuid)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => $uuid], ['foo' => 'uuid']);
        $this->assertTrue($v->passes());
    }

    /**
     * @dataProvider invalidUuidList
     */
    public function testValidateWithInvalidUuid($uuid)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => $uuid], ['foo' => 'uuid']);
        $this->assertFalse($v->passes());
    }

    public static function validUuidList()
    {
        return [
            ['a0a2a2d2-0b87-4a18-83f2-2529882be2de'],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'],
            ['00000000-0000-0000-0000-000000000000'],
            ['e60d3f48-95d7-4d8d-aad0-856f29a27da2'],
            ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-31e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-41e1-9b21-0800200c9a66'],
            ['ff6f8cb0-c57d-51e1-9b21-0800200c9a66'],
            ['FF6F8CB0-C57D-11E1-9B21-0800200C9A66'],
        ];
    }

    public static function invalidUuidList()
    {
        return [
            ['not a valid uuid so we can test this'],
            ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66'],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'.PHP_EOL],
            ['145a1e72-d11d-11e8-a8d5-f2801f1b9fd1 '],
            [' 145a1e72-d11d-11e8-a8d5-f2801f1b9fd1'],
            ['145a1e72-d11d-11e8-a8d5-f2z01f1b9fd1'],
            ['3f6f8cb0-c57d-11e1-9b21-0800200c9a6'],
            ['af6f8cb-c57d-11e1-9b21-0800200c9a66'],
            ['af6f8cb0c57d11e19b210800200c9a66'],
            ['ff6f8cb0-c57da-51e1-9b21-0800200c9a66'],
        ];
    }

    public function testValidateWithValidAscii()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'Dusseldorf'], ['foo' => 'ascii']);
        $this->assertTrue($v->passes());
    }

    public function testValidateWithInvalidAscii()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'Düsseldorf'], ['foo' => 'ascii']);
        $this->assertFalse($v->passes());
    }

    public function testValidateWithValidUlid()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '01gd6r360bp37zj17nxb55yv40'], ['foo' => 'ulid']);
        $this->assertTrue($v->passes());
    }

    public function testValidateWithInvalidUlid()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '01gd6r36-bp37z-17nx-55yv40'], ['foo' => 'ulid']);
        $this->assertFalse($v->passes());
    }

    public static function providesPassingExcludeIfData()
    {
        return [
            [
                [
                    'has_appointment' => ['required', 'bool'],
                    'appointment_date' => ['exclude_if:has_appointment,false', 'required', 'date'],
                ], [
                    'has_appointment' => false,
                    'appointment_date' => 'should be excluded',
                ], [
                    'has_appointment' => false,
                ],
            ],
            [
                [
                    'cat' => ['required', 'string'],
                    'mouse' => ['exclude_if:cat,Tom', 'required', 'file'],
                ], [
                    'cat' => 'Tom',
                    'mouse' => 'should be excluded',
                ], [
                    'cat' => 'Tom',
                ],
            ],
            [
                [
                    'has_appointment' => ['required', 'bool'],
                    'appointment_date' => ['exclude_if:has_appointment,false', 'required', 'date'],
                ], [
                    'has_appointment' => false,
                ], [
                    'has_appointment' => false,
                ],
            ],
            [
                [
                    'has_appointment' => ['nullable', 'bool'],
                    'appointment_date' => ['exclude_if:has_appointment,null', 'required', 'date'],
                ],
                [
                    'has_appointment' => true,
                    'appointment_date' => '2021-03-08',
                ],
                [
                    'has_appointment' => true,
                    'appointment_date' => '2021-03-08',
                ],
            ],
            [
                [
                    'has_appointment' => ['required', 'bool'],
                    'appointment_date' => ['exclude_if:has_appointment,false', 'required', 'date'],
                ], [
                    'has_appointment' => true,
                    'appointment_date' => '2019-12-13',
                ], [
                    'has_appointment' => true,
                    'appointment_date' => '2019-12-13',
                ],
            ],
            [
                [
                    'has_no_appointments' => ['required', 'bool'],
                    'has_doctor_appointment' => ['exclude_if:has_no_appointments,true', 'required', 'bool'],
                    'doctor_appointment_date' => ['exclude_if:has_no_appointments,true', 'exclude_if:has_doctor_appointment,false', 'required', 'date'],
                ], [
                    'has_no_appointments' => true,
                    'has_doctor_appointment' => true,
                    'doctor_appointment_date' => '2019-12-13',
                ], [
                    'has_no_appointments' => true,
                ],
            ],
            [
                [
                    'has_no_appointments' => ['required', 'bool'],
                    'has_doctor_appointment' => ['exclude_if:has_no_appointments,true', 'required', 'bool'],
                    'doctor_appointment_date' => ['exclude_if:has_no_appointments,true', 'exclude_if:has_doctor_appointment,false', 'required', 'date'],
                ], [
                    'has_no_appointments' => false,
                    'has_doctor_appointment' => false,
                    'doctor_appointment_date' => 'should be excluded',
                ], [
                    'has_no_appointments' => false,
                    'has_doctor_appointment' => false,
                ],
            ],
            'nested-01' => [
                [
                    'has_appointments' => ['required', 'bool'],
                    'appointments.*' => ['exclude_if:has_appointments,false', 'required', 'date'],
                ], [
                    'has_appointments' => false,
                    'appointments' => ['2019-05-15', '2020-05-15'],
                ], [
                    'has_appointments' => false,
                ],
            ],
            'nested-02' => [
                [
                    'has_appointments' => ['required', 'bool'],
                    'appointments.*.date' => ['exclude_if:has_appointments,false', 'required', 'date'],
                    'appointments.*.name' => ['exclude_if:has_appointments,false', 'required', 'string'],
                ], [
                    'has_appointments' => false,
                    'appointments' => [
                        ['date' => 'should be excluded', 'name' => 'should be excluded'],
                    ],
                ], [
                    'has_appointments' => false,
                ],
            ],
            'nested-03' => [
                [
                    'has_appointments' => ['required', 'bool'],
                    'appointments' => ['exclude_if:has_appointments,false', 'required', 'array'],
                    'appointments.*.date' => ['required', 'date'],
                    'appointments.*.name' => ['required', 'string'],
                ], [
                    'has_appointments' => false,
                    'appointments' => [
                        ['date' => 'should be excluded', 'name' => 'should be excluded'],
                    ],
                ], [
                    'has_appointments' => false,
                ],
            ],
            'nested-04' => [
                [
                    'has_appointments' => ['required', 'bool'],
                    'appointments.*.date' => ['required', 'date'],
                    'appointments' => ['exclude_if:has_appointments,false', 'required', 'array'],
                ], [
                    'has_appointments' => false,
                    'appointments' => [
                        ['date' => 'should be excluded', 'name' => 'should be excluded'],
                    ],
                ], [
                    'has_appointments' => false,
                ],
            ],
            'nested-05' => [
                [
                    'vehicles.*.type' => 'required|in:car,boat',
                    'vehicles.*.wheels' => 'exclude_if:vehicles.*.type,boat|required|numeric',
                ], [
                    'vehicles' => [
                        ['type' => 'car', 'wheels' => 4],
                        ['type' => 'boat', 'wheels' => 'should be excluded'],
                    ],
                ], [
                    'vehicles' => [
                        ['type' => 'car', 'wheels' => 4],
                        ['type' => 'boat'],
                    ],
                ],
            ],
            'nested-06' => [
                [
                    'vehicles.*.type' => 'required|in:car,boat',
                    'vehicles.*.wheels' => 'exclude_if:vehicles.*.type,boat|required|numeric',
                ], [
                    'vehicles' => [
                        ['type' => 'car', 'wheels' => 4],
                        ['type' => 'boat'],
                    ],
                ], [
                    'vehicles' => [
                        ['type' => 'car', 'wheels' => 4],
                        ['type' => 'boat'],
                    ],
                ],
            ],
            'nested-07' => [
                [
                    'vehicles.*.type' => 'required|in:car,boat',
                    'vehicles.*.wheels' => 'exclude_if:vehicles.*.type,boat|required|array',
                    'vehicles.*.wheels.*.color' => 'required|in:red,blue',
                    // In this bizzaro world example you can choose a custom shape for your wheels if they are red
                    'vehicles.*.wheels.*.shape' => 'exclude_unless:vehicles.*.wheels.*.color,red|required|in:square,round',
                ], [
                    'vehicles' => [
                        [
                            'type' => 'car', 'wheels' => [
                                ['color' => 'red', 'shape' => 'square'],
                                ['color' => 'blue', 'shape' => 'hexagon'],
                                ['color' => 'red', 'shape' => 'round', 'junk' => 'no rule, still present'],
                                ['color' => 'blue', 'shape' => 'triangle'],
                            ],
                        ],
                        ['type' => 'boat'],
                    ],
                ], [
                    'vehicles' => [
                        [
                            'type' => 'car', 'wheels' => [
                                ['color' => 'red', 'shape' => 'square'],
                                ['color' => 'blue'],
                                ['color' => 'red', 'shape' => 'round', 'junk' => 'no rule, still present'],
                                ['color' => 'blue'],
                            ],
                        ],
                        ['type' => 'boat'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providesPassingExcludeIfData
     */
    public function testExcludeIf($rules, $data, $expectedValidatedData)
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            $data,
            $rules
        );

        $passes = $validator->passes();

        if (! $passes) {
            $message = sprintf("Validation unexpectedly failed:\nRules: %s\nData: %s\nValidation error: %s",
                json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode($validator->messages()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        $this->assertTrue($passes, $message ?? '');

        $this->assertSame($expectedValidatedData, $validator->validated());
    }

    public static function providesFailingExcludeIfData()
    {
        return [
            [
                [
                    'has_appointment' => ['required', 'bool'],
                    'appointment_date' => ['exclude_if:has_appointment,false', 'required', 'date'],
                ], [
                    'has_appointment' => true,
                ], [
                    'appointment_date' => ['validation.required'],
                ],
            ],
            [
                [
                    'cat' => ['required', 'string'],
                    'mouse' => ['exclude_if:cat,Tom', 'required', 'file'],
                ], [
                    'cat' => 'Bob',
                    'mouse' => 'not a file',
                ], [
                    'mouse' => ['validation.file'],
                ],
            ],
            [
                [
                    'has_appointments' => ['required', 'bool'],
                    'appointments' => ['exclude_if:has_appointments,false', 'required', 'array'],
                    'appointments.*.date' => ['required', 'date'],
                    'appointments.*.name' => ['required', 'string'],
                ], [
                    'has_appointments' => true,
                    'appointments' => [
                        ['date' => 'invalid', 'name' => 'Bob'],
                        ['date' => '2019-05-15'],
                    ],
                ], [
                    'appointments.0.date' => ['validation.date'],
                    'appointments.1.name' => ['validation.required'],
                ],
            ],
            [
                [
                    'vehicles.*.price' => 'required|numeric',
                    'vehicles.*.type' => 'required|in:car,boat',
                    'vehicles.*.wheels' => 'exclude_if:vehicles.*.type,boat|required|numeric',
                ], [
                    'vehicles' => [
                        [
                            'price' => 100,
                            'type' => 'car',
                        ],
                        [
                            'price' => 500,
                            'type' => 'boat',
                        ],
                    ],
                ], [
                    'vehicles.0.wheels' => ['validation.required'],
                    // vehicles.1.wheels is not required, because type is not "car"
                ],
            ],
            'exclude-validation-error-01' => [
                [
                    'vehicles.*.type' => 'required|in:car,boat',
                    'vehicles.*.wheels' => 'exclude_if:vehicles.*.type,boat|required|array',
                    'vehicles.*.wheels.*.color' => 'required|in:red,blue',
                    // In this bizzaro world example you can choose a custom shape for your wheels if they are red
                    'vehicles.*.wheels.*.shape' => 'exclude_unless:vehicles.*.wheels.*.color,red|required|in:square,round',
                ], [
                    'vehicles' => [
                        [
                            'type' => 'car', 'wheels' => [
                                ['color' => 'red', 'shape' => 'square'],
                                ['color' => 'blue', 'shape' => 'hexagon'],
                                ['color' => 'red', 'shape' => 'hexagon'],
                                ['color' => 'blue', 'shape' => 'triangle'],
                            ],
                        ],
                        ['type' => 'boat', 'wheels' => 'should be excluded'],
                    ],
                ], [
                    // The blue wheels are excluded and are therefore not validated against the "in:square,round" rule
                    'vehicles.0.wheels.2.shape' => ['validation.in'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providesFailingExcludeIfData
     */
    public function testExcludeIfWhenValidationFails($rules, $data, $expectedMessages)
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            $data,
            $rules
        );

        $fails = $validator->fails();

        if (! $fails) {
            $message = sprintf("Validation unexpectedly passed:\nRules: %s\nData: %s",
                json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        $this->assertTrue($fails, $message ?? '');

        $this->assertSame($expectedMessages, $validator->messages()->toArray());
    }

    public static function providesPassingExcludeData()
    {
        return [
            [
                [
                    'has_appointment' => ['required', 'bool'],
                    'appointment_date' => ['exclude'],
                ], [
                    'has_appointment' => false,
                    'appointment_date' => 'should be excluded',
                ], [
                    'has_appointment' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providesPassingExcludeData
     */
    public function testExclude($rules, $data, $expectedValidatedData)
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            $data,
            $rules
        );

        $passes = $validator->passes();

        if (! $passes) {
            $message = sprintf("Validation unexpectedly failed:\nRules: %s\nData: %s\nValidation error: %s",
                json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode($validator->messages()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        $this->assertTrue($passes, $message ?? '');

        $this->assertSame($expectedValidatedData, $validator->validated());
    }

    public function testExcludeBeforeADependentRule()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'profile_id' => null,
                'type' => 'denied',
            ],
            [
                'type' => ['required', 'string', 'exclude'],
                'profile_id' => ['nullable', 'required_if:type,profile', 'integer'],
            ],
        );

        $this->assertTrue($validator->passes());
        $this->assertSame(['profile_id' => null], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'profile_id' => null,
                'type' => 'profile',
            ],
            [
                'type' => ['required', 'string', 'exclude'],
                'profile_id' => ['nullable', 'required_if:type,profile', 'integer'],
            ],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(['profile_id' => ['validation.required_if']], $validator->getMessageBag()->getMessages());
    }

    public function testExcludingArrays()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => [['name' => 'Mohamed', 'location' => 'cairo']]],
            ['users' => 'array', 'users.*.name' => 'string']
        );
        $validator->excludeUnvalidatedArrayKeys = false;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => [['name' => 'Mohamed', 'location' => 'cairo']]], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => [['name' => 'Mohamed', 'location' => 'cairo']]],
            ['users' => 'array', 'users.*.name' => 'string']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => [['name' => 'Mohamed']]], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['admin' => ['name' => 'Mohamed', 'location' => 'cairo'], 'users' => [['name' => 'Mohamed', 'location' => 'cairo']]],
            ['admin' => 'array', 'admin.name' => 'string', 'users' => 'array', 'users.*.name' => 'string']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['admin' => ['name' => 'Mohamed'], 'users' => [['name' => 'Mohamed']]], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => [['name' => 'Mohamed', 'location' => 'cairo']]],
            ['users' => 'array']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => [['name' => 'Mohamed', 'location' => 'cairo']]], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => ['mohamed', 'zain']],
            ['users' => 'array', 'users.*' => 'string']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => ['mohamed', 'zain']], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => ['admins' => [['name' => 'mohamed', 'job' => 'dev']], 'unvalidated' => 'foobar']],
            ['users' => 'array', 'users.admins' => 'array', 'users.admins.*.name' => 'string']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => ['admins' => [['name' => 'mohamed']]]], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['users' => [1, 2, 3]],
            ['users' => 'array|max:10']
        );
        $validator->excludeUnvalidatedArrayKeys = true;
        $this->assertTrue($validator->passes());
        $this->assertSame(['users' => [1, 2, 3]], $validator->validated());
    }

    public function testExcludeUnless()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Felix', 'mouse' => 'Jerry'],
            ['cat' => 'required|string', 'mouse' => 'exclude_unless:cat,Tom|required|string']
        );
        $this->assertTrue($validator->passes());
        $this->assertSame(['cat' => 'Felix'], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Felix'],
            ['cat' => 'required|string', 'mouse' => 'exclude_unless:cat,Tom|required|string']
        );
        $this->assertTrue($validator->passes());
        $this->assertSame(['cat' => 'Felix'], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Tom', 'mouse' => 'Jerry'],
            ['cat' => 'required|string', 'mouse' => 'exclude_unless:cat,Tom|required|string']
        );
        $this->assertTrue($validator->passes());
        $this->assertSame(['cat' => 'Tom', 'mouse' => 'Jerry'], $validator->validated());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Tom'],
            ['cat' => 'required|string', 'mouse' => 'exclude_unless:cat,Tom|required|string']
        );
        $this->assertTrue($validator->fails());
        $this->assertSame(['mouse' => ['validation.required']], $validator->messages()->toArray());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['foo' => true, 'bar' => 'baz'],
            ['foo' => 'nullable', 'bar' => 'exclude_unless:foo,null']
        );
        $this->assertTrue($validator->passes());
        $this->assertSame(['foo' => true], $validator->validated());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['bar' => 'Hello'], ['bar' => 'exclude_unless:foo,true']);
        $this->assertTrue($v->passes());
        $this->assertSame([], $v->validated());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['bar' => 'Hello'], ['bar' => 'exclude_unless:foo,null']);
        $this->assertTrue($v->passes());
        $this->assertSame(['bar' => 'Hello'], $v->validated());
    }

    public function testExcludeWithout()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['region' => 'South'],
            [
                'country' => 'exclude_without:region|nullable|required_with:region|string|min:3',
                'region' => 'exclude_without:country|nullable|required_with:country|string|min:3',
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(['country' => ['validation.required_with']], $validator->messages()->toArray());
    }

    public function testExcludeValuesAreReallyRemoved()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Tom', 'mouse' => 'Jerry'],
            ['cat' => 'required|string', 'mouse' => 'exclude_if:cat,Tom|required|string']
        );
        $this->assertTrue($validator->passes());
        $this->assertSame(['cat' => 'Tom'], $validator->validated());
        $this->assertSame(['cat' => 'Tom'], $validator->valid());
        $this->assertSame([], $validator->invalid());

        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            ['cat' => 'Tom', 'mouse' => null],
            ['cat' => 'required|string', 'mouse' => 'exclude_if:cat,Felix|required|string']
        );
        $this->assertTrue($validator->fails());
        $this->assertSame(['cat' => 'Tom'], $validator->valid());
        $this->assertSame(['mouse' => null], $validator->invalid());
    }

    public function testExcludeWithValuesAreReallyRemoved()
    {
        $validator = new Validator(
            $this->getIlluminateArrayTranslator(),
            [
                'cat' => 'Tom',
                'mouse' => 'Jerry',
            ],
            [
                'cat' => 'string',
                'mouse' => 'string|exclude_with:cat',
            ]
        );

        $this->assertTrue($validator->passes());
        $this->assertSame(['cat' => 'Tom'], $validator->validated());
        $this->assertSame(['cat' => 'Tom'], $validator->valid());
        $this->assertSame([], $validator->invalid());
    }

    public function testValidateFailsWithAsterisksAsDataKeys()
    {
        $post = ['data' => [0 => ['date' => '2019-01-24'], 1 => ['date' => 'blah'], '*' => ['date' => 'blah']]];

        $rules = ['data.*.date' => 'required|date'];

        $validator = new Validator($this->getIlluminateArrayTranslator(), $post, $rules);

        $this->assertTrue($validator->fails());
        $this->assertSame(['data.1.date' => ['validation.date'], 'data.*.date' => ['validation.date']], $validator->messages()->toArray());
    }

    public function testFailOnFirstError()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $data = [
            'foo' => 'bar',
            'age' => 30,
        ];
        $rules = [
            'foo' => ['required', 'string'],
            'baz' => ['required'],
            'age' => ['required', 'min:31'],
        ];

        $expectedFailOnFirstErrorDisableResult = [
            'baz' => [
                'validation.required',
            ],
            'age' => [
                'validation.min.string',
            ],
        ];
        $failOnFirstErrorDisable = new Validator($trans, $data, $rules);
        $this->assertFalse($failOnFirstErrorDisable->passes());
        $this->assertEquals($expectedFailOnFirstErrorDisableResult, $failOnFirstErrorDisable->getMessageBag()->getMessages());

        $expectedFailOnFirstErrorEnableResult = [
            'baz' => [
                'validation.required',
            ],
        ];
        $failOnFirstErrorEnable = new Validator($trans, $data, $rules, [], []);
        $failOnFirstErrorEnable->stopOnFirstFailure();
        $this->assertFalse($failOnFirstErrorEnable->passes());
        $this->assertEquals($expectedFailOnFirstErrorEnableResult, $failOnFirstErrorEnable->getMessageBag()->getMessages());
    }

    public function testArrayKeysValidationPassedWhenHasKeys()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = [
            'baz' => [
                'foo' => 'bar',
                'fee' => 'faa',
                'laa' => 'lee',
            ],
        ];

        $rules = [
            'baz' => [
                'array',
                'required_array_keys:foo,fee,laa',
            ],
        ];

        $validator = new Validator($trans, $data, $rules, [], []);
        $this->assertTrue($validator->passes());
    }

    public function testArrayKeysValidationPassedWithPartialMatch()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = [
            'baz' => [
                'foo' => 'bar',
                'fee' => 'faa',
                'laa' => 'lee',
            ],
        ];

        $rules = [
            'baz' => [
                'array',
                'required_array_keys:foo,fee',
            ],
        ];

        $validator = new Validator($trans, $data, $rules, [], []);
        $this->assertTrue($validator->passes());
    }

    public function testArrayKeysValidationFailsWithMissingKey()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_array_keys' => 'The :attribute field must contain entries for :values'], 'en');

        $data = [
            'baz' => [
                'foo' => 'bar',
                'fee' => 'faa',
                'laa' => 'lee',
            ],
        ];

        $rules = [
            'baz' => [
                'array',
                'required_array_keys:foo,fee,boo,bar',
            ],
        ];

        $validator = new Validator($trans, $data, $rules, [], []);
        $this->assertFalse($validator->passes());
        $this->assertSame(
            'The baz field must contain entries for foo, fee, boo, bar',
            $validator->messages()->first('baz')
        );
    }

    public function testArrayKeysValidationFailsWithNotAnArray()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_array_keys' => 'The :attribute field must contain entries for :values'], 'en');

        $data = [
            'baz' => 'no an array',
        ];

        $rules = [
            'baz' => [
                'required_array_keys:foo,fee,boo,bar',
            ],
        ];

        $validator = new Validator($trans, $data, $rules, [], []);
        $this->assertFalse($validator->passes());
        $this->assertSame(
            'The baz field must contain entries for foo, fee, boo, bar',
            $validator->messages()->first('baz')
        );
    }

    public function testArrayKeysWithDotIntegerMin()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $data = [
            'foo.bar' => -1,
        ];

        $rules = [
            'foo\.bar' => 'integer|min:1',
        ];

        $expectedResult = [
            'foo.bar' => [
                'validation.min.numeric',
            ],
        ];

        $validator = new Validator($trans, $data, $rules, [], []);
        $this->assertEquals($expectedResult, $validator->getMessageBag()->getMessages());
    }

    public function testItCanTranslateMessagesForClosureBasedRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.translated-error' => 'Translated error message.'], 'en');
        $rule = function ($attribute, $value, $fail) {
            $fail('validation.translated-error')->translate();
            $fail('validation.not-translated-message')->translate();
        };

        $validator = new Validator($trans, ['foo' => 'bar'], ['foo' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame([
            'foo' => [
                'Translated error message.',
                'validation.not-translated-message',
            ],
        ], $validator->messages()->messages());
    }

    public function testItCanSpecifyTheValidationErrorKeyForTheErrorMessageForClosureBasedRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $rule = function ($attribute, $value, $fail) {
            $fail('bar.baz', 'Another attribute error.');
            $fail('This attribute error.');
        };

        $validator = new Validator($trans, ['foo' => 'xxxx'], ['foo' => $rule]);

        $this->assertFalse($validator->passes());
        $this->assertSame([
            'bar.baz' => [
                'Another attribute error.',
            ],
            'foo' => [
                'This attribute error.',
            ],
        ], $validator->messages()->messages());
    }

    public function testItTrimsSpaceFromParameters()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $validator = new Validator($trans, [
            'min' => ' 20 ',
            'min_str' => ' abc ',
            'multiple_of' => ' 0.5 ',
            'between' => "\n 5 \n",
            'between_str' => ' abc ',
            'gt' => "\t5 ",
            'gt_field' => "\t5 ",
            'gt_str' => ' abc ',
            'lt' => "\t5 ",
            'lt_field' => "\t5 ",
            'lt_str' => ' abc ',
            'gte' => "\t5 ",
            'gte_field' => "\t5 ",
            'gte_str' => ' abc ',
            'lte' => "\t5 ",
            'lte_field' => "\t5 ",
            'lte_str' => ' abc ',
            'max' => ' 20 ',
            'max_str' => ' abc ',
            'size' => ' 20 ',
            'size_str' => ' abc ',
            'foo' => '4',
            ' foo' => ' 5',
            ' foo ' => ' 6 ',
            'foo_str' => 'abcd',
            ' foo_str' => ' abcd',
            ' foo_str ' => ' abcd ',
        ], [
            'min' => 'numeric|min: 20',
            'min_str' => 'min: 5',
            'multiple_of' => 'multiple_of:0.25 ',
            'between' => "numeric|between:\t 4, 6\n",
            'between_str' => "between:\t 5, 6\n",
            'gt' => 'numeric|gt: 4',
            'gt_field' => 'numeric|gt:foo',
            'gt_str' => 'gt:foo_str',
            'lt' => 'numeric|lt: 6',
            'lt_field' => 'numeric|lt: foo ',
            'lt_str' => 'lt: foo_str ',
            'gte' => 'numeric|gte: 5',
            'gte_field' => 'numeric|gte: foo',
            'gte_str' => 'gte: foo_str',
            'lte' => 'numeric|lte: 5',
            'lte_field' => 'numeric|lte: foo',
            'lte_str' => 'lte: foo_str',
            'max' => 'numeric|max: 20',
            'max_str' => 'max: 5',
            'size' => 'numeric|size: 20',
            'size_str' => 'size: 5',
        ], [], []);
        $this->assertTrue($validator->passes());

        $validator = new Validator($trans, [
            'min' => ' 20 ',
            'min_str' => ' abc ',
            'multiple_of' => ' 0.5 ',
            'between' => "\n 5 \n",
            'between_str' => ' abc ',
            'gt' => "\t5 ",
            'gt_field' => "\t5 ",
            'gt_str' => ' abc ',
            'lt' => "\t5 ",
            'lt_field' => "\t5 ",
            'lt_str' => ' abc ',
            'gte' => "\t5 ",
            'gte_field' => "\t5 ",
            'gte_str' => ' abc ',
            'lte' => "\t5 ",
            'lte_field' => "\t5 ",
            'lte_str' => ' abc ',
            'max' => ' 20 ',
            'max_str' => ' abc ',
            'size' => ' 20 ',
            'size_str' => ' abc ',
            'foo' => '4',
            ' foo' => ' 5',
            ' foo ' => ' 6 ',
            'foo_str' => 'abcd',
            ' foo_str' => ' abcd',
            ' foo_str ' => ' abcd ',
        ], [
            'min' => 'numeric|min: 21',
            'min_str' => 'min: 6',
            'multiple_of' => 'multiple_of:0.3 ',
            'between' => "numeric|between:\t 6, 7\n",
            'between_str' => "between:\t 6, 7\n",
            'gt' => 'numeric|gt: 5',
            'gt_field' => 'numeric|gt: foo ',
            'gt_str' => 'gt: foo_str',
            'lt' => 'numeric|lt: 5',
            'lt_field' => 'numeric|lt: foo',
            'lt_str' => 'lt: foo_str',
            'gte' => 'numeric|gte: 6',
            'gte_field' => 'numeric|gte: foo ',
            'gte_str' => 'gte: foo_str ',
            'lte' => 'numeric|lte: 4',
            'lte_field' => 'numeric|lte:foo',
            'lte_str' => 'lte:foo_str',
            'max' => 'numeric|max: 19',
            'max_str' => 'max: 4',
            'size' => 'numeric|size: 19',
            'size_str' => 'size: 4',
        ], [], []);
        $this->assertSame([
            'min',
            'min_str',
            'multiple_of',
            'between',
            'between_str',
            'gt',
            'gt_field',
            'gt_str',
            'lt',
            'lt_field',
            'lt_str',
            'gte',
            'gte_field',
            'gte_str',
            'lte',
            'lte_field',
            'lte_str',
            'max',
            'max_str',
            'size',
            'size_str',
        ], $validator->messages()->keys());
    }

    /** @dataProvider outsideRangeExponents */
    public function testItLimitsLengthOfScientificNotationExponent($value)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $validator = new Validator($trans, ['foo' => $value], ['foo' => 'numeric|min:3']);

        $this->expectException(MathException::class);
        $this->expectExceptionMessage('Scientific notation exponent outside of allowed range.');

        $validator->passes();
    }

    public static function outsideRangeExponents()
    {
        return [
            ['1.0e+1001'],
            ['1.0E+1001'],
            ['1.0e1001'],
            ['1.0E1001'],
            ['1.0e-1001'],
            ['1.0E-1001'],
        ];
    }

    /** @dataProvider withinRangeExponents */
    public function testItAllowsScientificNotationWithinRange($value, $rule)
    {
        $trans = $this->getIlluminateArrayTranslator();
        $validator = new Validator($trans, ['foo' => $value], ['foo' => ['numeric', $rule]]);

        $this->assertTrue($validator->passes());
    }

    public static function withinRangeExponents()
    {
        return [
            ['1.0e+1000', 'min:3'],
            ['1.0E+1000', 'min:3'],
            ['1.0e1000', 'min:3'],
            ['1.0E1000', 'min:3'],
            ['1.0e-1000', 'max:3'],
            ['1.0E-1000', 'max:3'],
        ];
    }

    public function testItCanConfigureAllowedExponentRange()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $validator = new Validator($trans, ['foo' => '1.0e-1000'], ['foo' => ['numeric', 'max:3']]);
        $scale = $attribute = $value = null;
        $withinRange = true;

        $validator->ensureExponentWithinAllowedRangeUsing(function () use (&$scale, &$attribute, &$value, &$withinRange) {
            [$scale, $attribute, $value] = func_get_args();

            return $withinRange;
        });

        $this->assertTrue($validator->passes());
        $this->assertSame(-1000, $scale);
        $this->assertSame('foo', $attribute);
        $this->assertSame('1.0e-1000', $value);

        $withinRange = false;
        $this->expectException(MathException::class);
        $this->expectExceptionMessage('Scientific notation exponent outside of allowed range.');

        $validator->passes();
    }

    protected function getTranslator()
    {
        return m::mock(TranslatorContract::class);
    }

    public function getIlluminateArrayTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }
}

class ImplicitTableModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}

class ExplicitTableModel extends Model
{
    protected $table = 'explicits';

    protected $guarded = [];

    public $timestamps = false;
}

class ExplicitPrefixedTableModel extends Model
{
    protected $table = 'prefix.explicits';

    protected $guarded = [];

    public $timestamps = false;
}

class ExplicitTableAndConnectionModel extends Model
{
    protected $table = 'explicits';

    protected $connection = 'connection';

    protected $guarded = [];

    public $timestamps = false;
}

class NonEloquentModel
{
}
