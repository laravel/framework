<?php

namespace Illuminate\Tests\Validation;

use DateTime;
use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Symfony\Component\HttpFoundation\File\File;

class ValidationValidatorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
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
        $v->setContainer(new \Illuminate\Container\Container);
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

    /**
     * @expectedException \Illuminate\Validation\ValidationException
     */
    public function testValidateThrowsOnFail()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar'], ['baz' => 'required']);

        $v->validate();
    }

    public function testValidateDoesntThrowOnPass()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'required']);

        $v->validate();
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
        $trans->shouldReceive('trans')->never();
        $v = new Validator($trans, ['foo' => 'taylor'], ['name' => 'Confirmed']);
        $this->assertTrue($v->passes());
        $this->assertEmpty($v->failed());
    }

    public function testSometimesCanSkipRequiredRules()
    {
        $trans = $this->getTranslator();
        $trans->shouldReceive('trans')->never();
        $v = new Validator($trans, [], ['name' => 'sometimes|required']);
        $this->assertTrue($v->passes());
        $this->assertEmpty($v->failed());
    }

    public function testInValidatableRulesReturnsValid()
    {
        $trans = $this->getTranslator();
        $trans->shouldReceive('trans')->never();
        $v = new Validator($trans, ['foo' => 'taylor'], ['name' => 'Confirmed']);
        $this->assertTrue($v->passes());
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
        $this->assertEquals('validation.string', $v->messages()->get('x')[0]);
        $this->assertEquals('validation.integer', $v->messages()->get('y')[0]);
        $this->assertEquals('validation.numeric', $v->messages()->get('z')[0]);
        $this->assertEquals('validation.array', $v->messages()->get('a')[0]);
        $this->assertEquals('validation.boolean', $v->messages()->get('b')[0]);
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
        $this->assertEquals('validation.integer', $v->messages()->get('x')[0]);

        $v = new Validator($trans, [
            'x' => 123, 'y' => null,
        ], [
            'x' => 'nullable|required_with:y|integer',
            'y' => 'nullable|required_with:x|integer',
        ]);
        $this->assertTrue($v->fails());
        $this->assertEquals('validation.required_with', $v->messages()->get('y')[0]);
    }

    public function testProperLanguageLineIsSet()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => 'required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');

        $this->assertEquals('required!', $v->messages()->first('name'));
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
        $this->assertEquals('foo taylor', $v->messages()->first('name'));
    }

    public function testClassBasedCustomReplacers()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, [], ['name' => 'required']);
        $v->setContainer($container = m::mock('Illuminate\Container\Container'));
        $v->addReplacer('required', 'Foo@bar');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock('StdClass'));
        $foo->shouldReceive('bar')->once()->andReturn('replaced!');
        $v->passes();
        $v->messages()->setFormat(':message');
        $this->assertEquals('replaced!', $v->messages()->first('name'));
    }

    public function testNestedAttributesAreReplacedInDimensions()
    {
        // Knowing that demo image.png has width = 3 and height = 2
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(__DIR__.'/fixtures/image.png', '', null, null, null, true);

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.dimensions' => ':min_width :max_height :ratio'], 'en');
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=10,max_height=20,ratio=1']);
        $v->messages()->setFormat(':message');
        $this->assertTrue($v->fails());
        $this->assertEquals('10 20 1', $v->messages()->first('x'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.dimensions' => ':width :height :ratio'], 'en');
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:min_width=10,max_height=20,ratio=1']);
        $v->messages()->setFormat(':message');
        $this->assertTrue($v->fails());
        $this->assertEquals(':width :height 1', $v->messages()->first('x'));
    }

    public function testAttributeNamesAreReplaced()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!', 'validation.attributes.name' => 'Name'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Name is required!', $v->messages()->first('name'));

        //set customAttributes by setter
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $customAttributes = ['name' => 'Name'];
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $v->addCustomAttributes($customAttributes);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $v->setAttributeNames(['name' => 'Name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':Attribute is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Name is required!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':ATTRIBUTE is required!'], 'en');
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('NAME is required!', $v->messages()->first('name'));
    }

    public function testAttributeNamesAreReplacedInArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['users' => [['country_code' => 'US'], ['country_code' => null]]], ['users.*.country_code' => 'Required']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('users.1.country_code is required!', $v->messages()->first('users.1.country_code'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines([
            'validation.string' => ':attribute must be a string!',
            'validation.attributes.name.*' => 'Any name',
        ], 'en');
        $v = new Validator($trans, ['name' => ['Jon', 2]], ['name.*' => 'string']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Any name must be a string!', $v->messages()->first('name.1'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.string' => ':attribute must be a string!'], 'en');
        $v = new Validator($trans, ['name' => ['Jon', 2]], ['name.*' => 'string']);
        $v->setAttributeNames(['name.*' => 'Any name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Any name must be a string!', $v->messages()->first('name.1'));

        $v = new Validator($trans, ['users' => [['name' => 'Jon'], ['name' => 2]]], ['users.*.name' => 'string']);
        $v->setAttributeNames(['users.*.name' => 'Any name']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Any name must be a string!', $v->messages()->first('users.1.name'));

        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $v = new Validator($trans, ['title' => ['nl' => '', 'en' => 'Hello']], ['title.*' => 'required'], [], ['title.nl' => 'Titel', 'title.en' => 'Title']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Titel is required!', $v->messages()->first('title.nl'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $trans->addLines(['validation.attributes' => ['names.*' => 'names']], 'en');
        $v = new Validator($trans, ['names' => [null, 'name']], ['names.*' => 'Required']);
        $v->messages()->setFormat(':message');
        $this->assertEquals('names is required!', $v->messages()->first('names.0'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required' => ':attribute is required!'], 'en');
        $trans->addLines(['validation.attributes' => ['names.*' => 'names']], 'en');
        $trans->addLines(['validation.attributes' => ['names.0' => 'First name']], 'en');
        $v = new Validator($trans, ['names' => [null, 'name']], ['names.*' => 'Required']);
        $v->messages()->setFormat(':message');
        $this->assertEquals('First name is required!', $v->messages()->first('names.0'));
    }

    public function testDisplayableValuesAreReplaced()
    {
        //required_if:foo,bar
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $trans->addLines(['validation.values.color.1' => 'red'], 'en');
        $v = new Validator($trans, ['color' => '1', 'bar' => ''], ['bar' => 'RequiredIf:color,1']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('The bar field is required when color is red.', $v->messages()->first('bar'));

        //in:foo,bar,...
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.in' => ':attribute must be included in :values.'], 'en');
        $trans->addLines(['validation.values.type.5' => 'Short'], 'en');
        $trans->addLines(['validation.values.type.300' => 'Long'], 'en');
        $v = new Validator($trans, ['type' => '4'], ['type' => 'in:5,300']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('type must be included in Short, Long.', $v->messages()->first('type'));

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
        $this->assertEquals('type must be included in Short, Long.', $v->messages()->first('type'));

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
        $this->assertEquals('type must be included in Short, Long.', $v->messages()->first('type'));
    }

    public function testDisplayableAttributesAreReplacedInCustomReplacers()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        $trans->addLines(['validation.attributes.firstname' => 'Firstname'], 'en');
        $trans->addLines(['validation.attributes.lastname' => 'Lastname'], 'en');
        $v = new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
        $v->addExtension('alliteration', function ($attribute, $value, $parameters, $validator) {
            $other = array_get($validator->getData(), $parameters[0]);

            return $value[0] == $other[0];
        });
        $v->addReplacer('alliteration', function ($message, $attribute, $rule, $parameters, $validator) {
            return str_replace(':other', $validator->getDisplayableAttribute($parameters[0]), $message);
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Lastname needs to begin with the same letter as Firstname', $v->messages()->first('lastname'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        $customAttributes = ['firstname' => 'Firstname', 'lastname' => 'Lastname'];
        $v = new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
        $v->addCustomAttributes($customAttributes);
        $v->addExtension('alliteration', function ($attribute, $value, $parameters, $validator) {
            $other = array_get($validator->getData(), $parameters[0]);

            return $value[0] == $other[0];
        });
        $v->addReplacer('alliteration', function ($message, $attribute, $rule, $parameters, $validator) {
            return str_replace(':other', $validator->getDisplayableAttribute($parameters[0]), $message);
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('Lastname needs to begin with the same letter as Firstname', $v->messages()->first('lastname'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.alliteration' => ':attribute needs to begin with the same letter as :other'], 'en');
        $customAttributes = ['firstname' => 'Firstname', 'lastname' => 'Lastname'];
        $v = new Validator($trans, ['firstname' => 'Bob', 'lastname' => 'Smith'], ['lastname' => 'alliteration:firstname']);
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
        $this->assertEquals('really required!', $v->messages()->first('name'));
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
        $this->assertEquals('all are really required!', $v->messages()->first('name.0'));
        $this->assertEquals('all are really required!', $v->messages()->first('name.1'));
        $this->assertEquals('english is required!', $v->messages()->first('lang.en'));
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
        $this->assertEquals('should be integer!', $v->messages()->first('validation.custom.0'));
        $this->assertEquals('should be integer!', $v->messages()->first('validation.custom.1'));
    }

    public function testInlineValidationMessagesAreRespected()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required'], ['name.required' => 'require it please!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('require it please!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ''], ['name' => 'Required'], ['required' => 'require it please!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('require it please!', $v->messages()->first('name'));
    }

    public function testInlineValidationMessagesAreRespectedWithAsterisks()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => ['', '']], ['name.*' => 'required|max:255'], ['name.*.required' => 'all must be required!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('all must be required!', $v->messages()->first('name.0'));
        $this->assertEquals('all must be required!', $v->messages()->first('name.1'));
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

        $v = new Validator($trans, ['foo' => new \Symfony\Component\HttpFoundation\File\File('/tmp/foo', false)], ['foo' => 'Array']);
        $this->assertFalse($v->passes());
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
        $v = new Validator($trans, ['foo' => true], ['bar' => 'required_if:foo,true']);
        $this->assertTrue($v->fails());

        // error message when passed multiple values (required_if:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_if' => 'The :attribute field is required when :other is :value.'], 'en');
        $v = new Validator($trans, ['first' => 'dayle', 'last' => ''], ['last' => 'RequiredIf:first,taylor,dayle']);
        $this->assertFalse($v->passes());
        $this->assertEquals('The last field is required when first is dayle.', $v->messages()->first('last'));
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

        // error message when passed multiple values (required_unless:foo,bar,baz)
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.required_unless' => 'The :attribute field is required unless :other is in :values.'], 'en');
        $v = new Validator($trans, ['first' => 'dayle', 'last' => ''], ['last' => 'RequiredUnless:first,taylor,sven']);
        $this->assertFalse($v->passes());
        $this->assertEquals('The last field is required unless first is in taylor, sven.', $v->messages()->first('last'));
    }

    public function testFailedFileUploads()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // If file is not successfully uploaded validation should fail with a
        // 'uploaded' error message instead of the original rule.
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $file->shouldReceive('isValid')->andReturn(false);
        $file->shouldNotReceive('getSize');
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.uploaded'], $v->errors()->get('photo'));

        // Even "required" will not run if the file failed to upload.
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $file->shouldReceive('isValid')->once()->andReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.uploaded'], $v->errors()->get('photo'));

        // It should only fail with that rule if a validation rule implies it's
        // a file. Otherwise it should fail with the regular rule.
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $file->shouldReceive('isValid')->andReturn(false);
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'string']);
        $this->assertTrue($v->fails());
        $this->assertEquals(['validation.string'], $v->errors()->get('photo'));

        // Validation shouldn't continue if a file failed to upload.
        $file = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
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
        $this->assertEquals('The value of foo.2 does not exist in bar.*.', $v->messages()->first('foo.2'));
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

        $v = new Validator($trans, ['foo' => 'bar'], ['foo' => 'Different:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'bar', 'baz' => 'bar'], ['foo' => 'Different:baz']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '1e2', 'baz' => '100'], ['foo' => 'Different:baz']);
        $this->assertTrue($v->passes());
    }

    public function testValidateAccepted()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => 'no'], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => null], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, [], ['foo' => 'Accepted']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 0], ['foo' => 'Accepted']);
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

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Size:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Size:4']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Size:3']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
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

        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Numeric|Between:1,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Between:1,5']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Between:1,2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Between:1,5']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Between:1,2']);
        $this->assertFalse($v->passes());
    }

    public function testValidateMin()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => '3'], ['foo' => 'Min:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => 'anc'], ['foo' => 'Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => '2'], ['foo' => 'Numeric|Min:3']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => '5'], ['foo' => 'Numeric|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3, 4]], ['foo' => 'Array|Min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2]], ['foo' => 'Array|Min:3']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Min:2']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->setMethods(['getSize'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
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

        $v = new Validator($trans, ['foo' => '22'], ['foo' => 'Numeric|Max:33']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Max:4']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'Array|Max:2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['isValid', 'getSize'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->expects($this->any())->method('isValid')->will($this->returnValue(true));
        $file->expects($this->at(1))->method('getSize')->will($this->returnValue(3072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertTrue($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['isValid', 'getSize'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->expects($this->at(0))->method('isValid')->will($this->returnValue(true));
        $file->expects($this->at(1))->method('getSize')->will($this->returnValue(4072));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:2']);
        $this->assertFalse($v->passes());

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['isValid'])->setConstructorArgs([__FILE__, basename(__FILE__)])->getMock();
        $file->expects($this->any())->method('isValid')->will($this->returnValue(false));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:10']);
        $this->assertFalse($v->passes());
    }

    public function testProperMessagesAreReturnedForSizes()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.min.numeric' => 'numeric', 'validation.size.string' => 'string', 'validation.max.file' => 'file'], 'en');
        $v = new Validator($trans, ['name' => '3'], ['name' => 'Numeric|Min:5']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('numeric', $v->messages()->first('name'));

        $v = new Validator($trans, ['name' => 'asasdfadsfd'], ['name' => 'Size:2']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('string', $v->messages()->first('name'));

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['getSize', 'isValid'])->setConstructorArgs([__FILE__, false])->getMock();
        $file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
        $file->expects($this->any())->method('isValid')->will($this->returnValue(true));
        $v = new Validator($trans, ['photo' => $file], ['photo' => 'Max:3']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('file', $v->messages()->first('photo'));
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

        $v = new Validator($trans, ['foo' => ['foo', 'bar']], ['foo.*' => 'distinct']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 1], 'baz' => ['id' => 1]]], ['foo.*.id' => 'distinct']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => ['bar' => ['id' => 1], 'baz' => ['id' => 2]]], ['foo.*.id' => 'distinct']);
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

        $v = new Validator($trans, ['foo' => ['foo', 'foo']], ['foo.*' => 'distinct'], ['foo.*.distinct' => 'There is a duplication!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('There is a duplication!', $v->messages()->first('foo.0'));
        $this->assertEquals('There is a duplication!', $v->messages()->first('foo.1'));
    }

    public function testValidateUnique()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:connection.users']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with('connection');
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,1']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id', [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,1,id_col']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id_col', [])->andReturn(2);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['users' => [['id' => 1, 'email' => 'foo']]], ['users.*.email' => 'Unique:users,email,[users.*.id]']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', '1', 'id', [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Unique:users,email_addr,NULL,id_col,foo,bar']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', null, 'id_col', ['foo' => 'bar'])->andReturn(2);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());
    }

    public function testValidateUniqueAndExistsSendsCorrectFieldNameToDBWithArrays()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, [['email' => 'foo', 'type' => 'bar']], [
            '*.email' => 'unique:users', '*.type' => 'exists:user_types',
        ]);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->twice()->with(null);
        $mock->shouldReceive('getCount')->with('users', 'email', 'foo', null, null, [])->andReturn(0);
        $mock->shouldReceive('getCount')->with('user_types', 'type', 'bar', null, null, [])->andReturn(1);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $closure = function () {
        };
        $v = new Validator($trans, [['email' => 'foo', 'type' => 'bar']], [
            '*.email' => (new Unique('users'))->where($closure),
            '*.type' => (new Exists('user_types'))->where($closure),
        ]);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
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
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(true);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:users,email,account_id,1,name,taylor']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, ['account_id' => 1, 'name' => 'taylor'])->andReturn(true);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:users,email_addr']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', null, null, [])->andReturn(false);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => ['foo']], ['email' => 'Exists:users,email_addr']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getMultiCount')->once()->with('users', 'email_addr', ['foo'], [])->andReturn(false);
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['email' => 'foo'], ['email' => 'Exists:connection.users']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with('connection');
        $mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null, [])->andReturn(true);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());
    }

    public function testValidationExistsIsNotCalledUnnecessarily()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => 'foo'], ['id' => 'Integer|Exists:users,id']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('getCount')->never();
        $v->setPresenceVerifier($mock);
        $this->assertFalse($v->passes());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['id' => '1'], ['id' => 'Integer|Exists:users,id']);
        $mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
        $mock->shouldReceive('setConnection')->once()->with(null);
        $mock->shouldReceive('getCount')->once()->with('users', 'id', '1', null, null, [])->andReturn(true);
        $v->setPresenceVerifier($mock);
        $this->assertTrue($v->passes());
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

    public function testValidateEmail()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'Email']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'foo@gmail.com'], ['x' => 'Email']);
        $this->assertTrue($v->passes());
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

    public function validUrls()
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
        ];
    }

    public function invalidUrls()
    {
        return [
            ['aslsdlks'],
            ['google.com'],
            ['://google.com'],
            ['http ://google.com'],
            ['http:/google.com'],
            ['http://goog_le.com'],
            ['http://google.com::aa'],
            ['http://google.com:aa'],
            ['http://127.0.0.1:aa'],
            ['http://[::1'],
            ['foo://bar'],
            ['javascript://test%0Aalert(321)'],
        ];
    }

    public function testValidateActiveUrl()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'aslsdlks'], ['x' => 'active_url']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => ['fdsfs', 'fdsfds']], ['x' => 'active_url']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'http://google.com'], ['x' => 'active_url']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://www.google.com'], ['x' => 'active_url']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'http://www.google.com/about'], ['x' => 'active_url']);
        $this->assertTrue($v->passes());
    }

    public function testValidateImage()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, null, true];

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->will($this->returnValue('php'));
        $v = new Validator($trans, ['x' => $file], ['x' => 'Image']);
        $this->assertFalse($v->passes());

        $file2 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->will($this->returnValue('jpeg'));
        $v = new Validator($trans, ['x' => $file2], ['x' => 'Image']);
        $this->assertTrue($v->passes());

        $file3 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file3->expects($this->any())->method('guessExtension')->will($this->returnValue('gif'));
        $v = new Validator($trans, ['x' => $file3], ['x' => 'Image']);
        $this->assertTrue($v->passes());

        $file4 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file4->expects($this->any())->method('guessExtension')->will($this->returnValue('bmp'));
        $v = new Validator($trans, ['x' => $file4], ['x' => 'Image']);
        $this->assertTrue($v->passes());

        $file5 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file5->expects($this->any())->method('guessExtension')->will($this->returnValue('png'));
        $v = new Validator($trans, ['x' => $file5], ['x' => 'Image']);
        $this->assertTrue($v->passes());

        $file6 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file6->expects($this->any())->method('guessExtension')->will($this->returnValue('svg'));
        $v = new Validator($trans, ['x' => $file6], ['x' => 'Image']);
        $this->assertTrue($v->passes());
    }

    /**
     * @group dimension
     */
    public function testValidateImageDimensions()
    {
        // Knowing that demo image.png has width = 3 and height = 2
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(__DIR__.'/fixtures/image.png', '', null, null, null, true);
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
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(__DIR__.'/fixtures/image2.png', '', null, null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        // Ensure validation doesn't erroneously fail when ratio has no fractional part
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=2/1']);
        $this->assertTrue($v->passes());

        // This test fails without suppressing warnings on getimagesize() due to a read error.
        $emptyUploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(__DIR__.'/fixtures/empty.png', '', null, null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['x' => $emptyUploadedFile], ['x' => 'dimensions:min_width=1']);
        $this->assertTrue($v->fails());

        // Knowing that demo image3.png has width = 7 and height = 10
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(__DIR__.'/fixtures/image3.png', '', null, null, null, true);
        $trans = $this->getIlluminateArrayTranslator();

        // Ensure validation doesn't erroneously fail when ratio has no fractional part
        $v = new Validator($trans, ['x' => $uploadedFile], ['x' => 'dimensions:ratio=2/3']);
        $this->assertTrue($v->passes());
    }

    /**
     * @requires extension fileinfo
     */
    public function testValidateMimetypes()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, null, true];

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->will($this->returnValue('php'));

        $v = new Validator($trans, ['x' => $file], ['x' => 'mimetypes:text/x-php']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => $file], ['x' => 'mimetypes:text/*']);
        $this->assertTrue($v->passes());
    }

    public function testValidateMime()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $uploadedFile = [__FILE__, '', null, null, null, true];

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension'])->setConstructorArgs($uploadedFile)->getMock();
        $file->expects($this->any())->method('guessExtension')->will($this->returnValue('php'));
        $v = new Validator($trans, ['x' => $file], ['x' => 'mimes:php']);
        $this->assertTrue($v->passes());

        $file2 = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')->setMethods(['guessExtension', 'isValid'])->setConstructorArgs($uploadedFile)->getMock();
        $file2->expects($this->any())->method('guessExtension')->will($this->returnValue('php'));
        $file2->expects($this->any())->method('isValid')->will($this->returnValue(false));
        $v = new Validator($trans, ['x' => $file2], ['x' => 'mimes:php']);
        $this->assertFalse($v->passes());
    }

    /**
     * @requires extension fileinfo
     */
    public function testValidateFile()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile(__FILE__, '', null, null, null, true);

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
        $v = new Validator($trans, ['x' => 'aslsdlks
1
1'], ['x' => 'Alpha']);
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

        $v = new Validator($trans, ['foo' => 'GMT'], ['foo' => 'Timezone']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => ['this_is_not_a_timezone']], ['foo' => 'Timezone']);
        $this->assertFalse($v->passes());
    }

    public function testValidateRegex()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'asdasdf'], ['x' => 'Regex:/^([a-z])+$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'aasd234fsd1'], ['x' => 'Regex:/^([a-z])+$/i']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'a,b'], ['x' => 'Regex:/^a,b$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '12'], ['x' => 'Regex:/^12$/i']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 123], ['x' => 'Regex:/^123$/i']);
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

        $v = new Validator($trans, ['x' => new DateTime()], ['x' => 'date']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01 17:43:59'], ['x' => 'date_format:Y-m-d H:i:s']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '01/01/2001'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '22000-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '00-01-01'], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => ['Not', 'a', 'date']], ['x' => 'date_format:Y-m-d']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00Atlantic/Azores'], ['x' => 'date_format:Y-m-d\TH:i:se']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00Z'], ['x' => 'date_format:Y-m-d\TH:i:sT']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00+0000'], ['x' => 'date_format:Y-m-d\TH:i:sO']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2000-01-01T00:00:00+00:30'], ['x' => 'date_format:Y-m-d\TH:i:sP']);
        $this->assertTrue($v->passes());
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

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after:yesterday|before:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('d/m/Y')], ['x' => 'date_format:d/m/Y|after:tomorrow|before:yesterday']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'after:yesterday|before:tomorrow']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => date('Y-m-d')], ['x' => 'after:tomorrow|before:yesterday']);
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

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'date_format:d/m/Y|before_or_equal:31/12/2012']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'date_format:d/m/Y|before_or_equal:29/12/2012']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-15']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-14']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '2012-01-15'], ['x' => 'after_or_equal:2012-01-16']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'date_format:d/m/Y|after_or_equal:31/12/2012']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => '31/12/2012'], ['x' => 'date_format:d/m/Y|after_or_equal:02/01/2013']);
        $this->assertTrue($v->fails());
    }

    public function testSometimesAddingRules()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', 'Confirmed', function ($i) {
            return $i->x == 'foo';
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
            return $i->x == 'bar';
        });
        $this->assertEquals(['x' => ['Required']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', 'Foo|Bar', function ($i) {
            return $i->x == 'foo';
        });
        $this->assertEquals(['x' => ['Required', 'Foo', 'Bar']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['x' => 'foo'], ['x' => 'Required']);
        $v->sometimes('x', ['Foo', 'Bar:Baz'], function ($i) {
            return $i->x == 'foo';
        });
        $this->assertEquals(['x' => ['Required', 'Foo', 'Bar:Baz']], $v->getRules());

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => [['name' => 'first', 'title' => null]]], []);
        $v->sometimes('foo.*.name', 'Required|String', function ($i) {
            return is_null($i['foo'][0]['title']);
        });
        $this->assertEquals(['foo.0.name' => ['Required', 'String']], $v->getRules());
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
        $this->assertEquals('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo_bar' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtension('FooBar', function () {
            return false;
        });
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtension('FooBar', function () {
            return false;
        });
        $v->setFallbackMessages(['foo_bar' => 'foo!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('foo!', $v->messages()->first('name'));

        $trans = $this->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo_bar']);
        $v->addExtensions(['FooBar' => function () {
            return false;
        }]);
        $v->setFallbackMessages(['foo_bar' => 'foo!']);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('foo!', $v->messages()->first('name'));
    }

    public function testClassBasedCustomValidators()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo']);
        $v->setContainer($container = m::mock('Illuminate\Container\Container'));
        $v->addExtension('foo', 'Foo@bar');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock('StdClass'));
        $foo->shouldReceive('bar')->once()->andReturn(false);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('foo!', $v->messages()->first('name'));
    }

    public function testClassBasedCustomValidatorsUsingConventionalMethod()
    {
        $trans = $this->getIlluminateArrayTranslator();
        $trans->addLines(['validation.foo' => 'foo!'], 'en');
        $v = new Validator($trans, ['name' => 'taylor'], ['name' => 'foo']);
        $v->setContainer($container = m::mock('Illuminate\Container\Container'));
        $v->addExtension('foo', 'Foo');
        $container->shouldReceive('make')->once()->with('Foo')->andReturn($foo = m::mock('StdClass'));
        $foo->shouldReceive('validate')->once()->andReturn(false);
        $this->assertFalse($v->passes());
        $v->messages()->setFormat(':message');
        $this->assertEquals('foo!', $v->messages()->first('name'));
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
            return array_get($v->getData(), $name) == 'Jamie';
        });
        $this->assertTrue($v->passes());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionThrownOnIncorrectParameterCount()
    {
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

    /**
     * @group validation
     */
    public function testSometimesOnArraysInImplicitRules()
    {
        $trans = $this->getIlluminateArrayTranslator();

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

        $data = ['people' => [
            ['cars' => [['model' => 2005], []]],
        ]];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());

        $data = ['people' => [
            ['name' => 'test', 'cars' => [['model' => 2005], ['name' => 'test2']]],
        ]];
        $v = new Validator($trans, $data, ['people.*.cars.*.model' => 'required']);
        $this->assertFalse($v->passes());

        $data = ['people' => [
            ['phones' => ['iphone', 'android'], 'cars' => [['model' => 2005], ['name' => 'test2']]],
        ]];
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

        $v = new Validator($trans, ['foo' => ['bar' => ''], 'foo.bar' => 'valid'], ['foo.bar' => 'required']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar' => 'valid'], 'foo.bar' => ''], ['foo\.bar' => 'required']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => ['bar.baz' => '']], ['foo.bar\.baz' => 'required']);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => [['bar.baz' => ''], ['bar.baz' => '']]], ['foo.*.bar\.baz' => 'required']);
        $this->assertTrue($v->fails());
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

        $v = new Validator($trans, ['foo' => [1, 2, 3]], ['foo' => 'size:4']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3, 4]], ['foo' => 'size:4']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [1, 2, 3, 4]], ['foo.*' => 'integer', 'foo.0' => 'required']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [['bar' => [1, 2, 3]], ['bar' => [1, 2, 3]]]], ['foo.*.bar' => 'size:4']);
        $this->assertFalse($v->passes());

        $v = new Validator($trans,
            ['foo' => [['bar' => [1, 2, 3]], ['bar' => [1, 2, 3]]]], ['foo.*.bar' => 'min:3']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans,
            ['foo' => [['bar' => [1, 2, 3]], ['bar' => [1, 2, 3]]]], ['foo.*.bar' => 'between:3,6']);
        $this->assertTrue($v->passes());

        $v = new Validator($trans,
            ['foo' => [['name' => 'first', 'votes' => [1, 2]], ['name' => 'second', 'votes' => ['something', 2]]]],
            ['foo.*.votes' => ['Required', 'Size:2']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans,
            ['foo' => [['name' => 'first', 'votes' => [1, 2, 3]], ['name' => 'second', 'votes' => ['something', 2]]]],
            ['foo.*.votes' => ['Required', 'Size:2']]);
        $this->assertFalse($v->passes());
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
        $v = new Validator($trans, ['foo' => [
            ['password' => 'foo0', 'password_confirmation' => 'foo0'],
            ['password' => 'foo1', 'password_confirmation' => 'foo1'],
        ]], ['foo.*.password' => 'confirmed']);
        $this->assertTrue($v->passes());

        // nested confirmed passes
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['password' => 'bar0', 'password_confirmation' => 'bar0'],
                ['password' => 'bar1', 'password_confirmation' => 'bar1'],
            ]],
            ['bar' => [
                ['password' => 'bar2', 'password_confirmation' => 'bar2'],
                ['password' => 'bar3', 'password_confirmation' => 'bar3'],
            ]],
        ]], ['foo.*.bar.*.password' => 'confirmed']);
        $this->assertTrue($v->passes());

        // confirmed fails
        $v = new Validator($trans, ['foo' => [
            ['password' => 'foo0', 'password_confirmation' => 'bar0'],
            ['password' => 'foo1'],
        ]], ['foo.*.password' => 'confirmed']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.password'));
        $this->assertTrue($v->messages()->has('foo.1.password'));

        // nested confirmed fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['password' => 'bar0'],
                ['password' => 'bar1', 'password_confirmation' => 'bar2'],
            ]],
        ]], ['foo.*.bar.*.password' => 'confirmed']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.password'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.password'));
    }

    public function testValidateImplicitEachWithAsterisksDifferent()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // different passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'foo', 'last' => 'bar'],
            ['name' => 'bar', 'last' => 'foo'],
        ]], ['foo.*.name' => ['different:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested different passes
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => 'foo', 'last' => 'bar'],
                ['name' => 'bar', 'last' => 'foo'],
            ]],
        ]], ['foo.*.bar.*.name' => ['different:foo.*.bar.*.last']]);
        $this->assertTrue($v->passes());

        // different fails
        $v = new Validator($trans, ['foo' => [
            ['name' => 'foo', 'last' => 'foo'],
            ['name' => 'bar', 'last' => 'bar'],
        ]], ['foo.*.name' => ['different:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested different fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => 'foo', 'last' => 'foo'],
                ['name' => 'bar', 'last' => 'bar'],
            ]],
        ]], ['foo.*.bar.*.name' => ['different:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksSame()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // same passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'foo', 'last' => 'foo'],
            ['name' => 'bar', 'last' => 'bar'],
        ]], ['foo.*.name' => ['same:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested same passes
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => 'foo', 'last' => 'foo'],
                ['name' => 'bar', 'last' => 'bar'],
            ]],
        ]], ['foo.*.bar.*.name' => ['same:foo.*.bar.*.last']]);
        $this->assertTrue($v->passes());

        // same fails
        $v = new Validator($trans, ['foo' => [
            ['name' => 'foo', 'last' => 'bar'],
            ['name' => 'bar', 'last' => 'foo'],
        ]], ['foo.*.name' => ['same:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested same fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => 'foo', 'last' => 'bar'],
                ['name' => 'bar', 'last' => 'foo'],
            ]],
        ]], ['foo.*.bar.*.name' => ['same:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequired()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first'],
            ['name' => 'second'],
        ]], ['foo.*.name' => ['Required']]);
        $this->assertTrue($v->passes());

        // nested required passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first'],
            ['name' => 'second'],
        ]], ['foo.*.name' => ['Required']]);
        $this->assertTrue($v->passes());

        // required fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null],
            ['name' => null, 'last' => 'last'],
        ]], ['foo.*.name' => ['Required']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null],
                ['name' => null],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredIf()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_if passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'foo'],
            ['last' => 'bar'],
        ]], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // nested required_if passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'foo'],
            ['last' => 'bar'],
        ]], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // required_if fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'foo'],
            ['name' => null, 'last' => 'foo'],
        ]], ['foo.*.name' => ['Required_if:foo.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_if fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'last' => 'foo'],
                ['name' => null, 'last' => 'foo'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_if:foo.*.bar.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredUnless()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_unless passes
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'foo'],
            ['name' => 'second', 'last' => 'bar'],
        ]], ['foo.*.name' => ['Required_unless:foo.*.last,foo']]);
        $this->assertTrue($v->passes());

        // nested required_unless passes
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'foo'],
            ['name' => 'second', 'last' => 'foo'],
        ]], ['foo.*.bar.*.name' => ['Required_unless:foo.*.bar.*.last,foo']]);
        $this->assertTrue($v->passes());

        // required_unless fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'baz'],
            ['name' => null, 'last' => 'bar'],
        ]], ['foo.*.name' => ['Required_unless:foo.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_unless fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'last' => 'bar'],
                ['name' => null, 'last' => 'bar'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_unless:foo.*.bar.*.last,foo']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWith()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_with passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'last'],
            ['name' => 'second', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertTrue($v->passes());

        // nested required_with passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'last'],
            ['name' => 'second', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertTrue($v->passes());

        // required_with fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'last'],
            ['name' => null, 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_with:foo.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        $v = new Validator($trans, ['fields' => [
            'fr' => ['name' => '', 'content' => 'ragnar'],
            'es' => ['name' => '', 'content' => 'lagertha'],
        ]], ['fields.*.name' => 'required_with:fields.*.content']);
        $this->assertFalse($v->passes());

        // nested required_with fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'last' => 'last'],
                ['name' => null, 'last' => 'last'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_with:foo.*.bar.*.last']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithAll()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_with_all passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'last', 'middle' => 'middle'],
            ['name' => 'second', 'last' => 'last', 'middle' => 'middle'],
        ]], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // nested required_with_all passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'last' => 'last', 'middle' => 'middle'],
            ['name' => 'second', 'last' => 'last', 'middle' => 'middle'],
        ]], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_with_all fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'last', 'middle' => 'middle'],
            ['name' => null, 'last' => 'last', 'middle' => 'middle'],
        ]], ['foo.*.name' => ['Required_with_all:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_with_all fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'last' => 'last', 'middle' => 'middle'],
                ['name' => null, 'last' => 'last', 'middle' => 'middle'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_with_all:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithout()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_without passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'middle' => 'middle'],
            ['name' => 'second', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // nested required_without passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first', 'middle' => 'middle'],
            ['name' => 'second', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_without fails
        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'last' => 'last'],
            ['name' => null, 'middle' => 'middle'],
        ]], ['foo.*.name' => ['Required_without:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_without fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'last' => 'last'],
                ['name' => null, 'middle' => 'middle'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_without:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksRequiredWithoutAll()
    {
        $trans = $this->getIlluminateArrayTranslator();

        // required_without_all passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first'],
            ['name' => null, 'middle' => 'middle'],
            ['name' => null, 'middle' => 'middle', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        // required_without_all fails
        // nested required_without_all passes
        $v = new Validator($trans, ['foo' => [
            ['name' => 'first'],
            ['name' => null, 'middle' => 'middle'],
            ['name' => null, 'middle' => 'middle', 'last' => 'last'],
        ]], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [
            ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
            ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
        ]], ['foo.*.name' => ['Required_without_all:foo.*.last,foo.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.name'));
        $this->assertTrue($v->messages()->has('foo.1.name'));

        // nested required_without_all fails
        $v = new Validator($trans, ['foo' => [
            ['bar' => [
                ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
                ['name' => null, 'foo' => 'foo', 'bar' => 'bar'],
            ]],
        ]], ['foo.*.bar.*.name' => ['Required_without_all:foo.*.bar.*.last,foo.*.bar.*.middle']]);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->messages()->has('foo.0.bar.0.name'));
        $this->assertTrue($v->messages()->has('foo.0.bar.1.name'));
    }

    public function testValidateImplicitEachWithAsterisksBeforeAndAfter()
    {
        $trans = $this->getIlluminateArrayTranslator();

        $v = new Validator($trans, ['foo' => [
            ['start' => '2016-04-19', 'end' => '2017-04-19'],
        ]], ['foo.*.start' => ['before:foo.*.end']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [
            ['start' => '2016-04-19', 'end' => '2017-04-19'],
        ]], ['foo.*.end' => ['before:foo.*.start']]);
        $this->assertTrue($v->fails());

        $v = new Validator($trans, ['foo' => [
            ['start' => '2016-04-19', 'end' => '2017-04-19'],
        ]], ['foo.*.end' => ['after:foo.*.start']]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['foo' => [
            ['start' => '2016-04-19', 'end' => '2017-04-19'],
        ]], ['foo.*.start' => ['after:foo.*.end']]);
        $this->assertTrue($v->fails());
    }

    public function testGetLeadingExplicitAttributePath()
    {
        $this->assertEquals(null, \Illuminate\Validation\ValidationData::getLeadingExplicitAttributePath('*.email'));
        $this->assertEquals('foo', \Illuminate\Validation\ValidationData::getLeadingExplicitAttributePath('foo.*'));
        $this->assertEquals('foo.bar', \Illuminate\Validation\ValidationData::getLeadingExplicitAttributePath('foo.bar.*.baz'));
        $this->assertEquals('foo.bar.1', \Illuminate\Validation\ValidationData::getLeadingExplicitAttributePath('foo.bar.1'));
    }

    public function testExtractDataFromPath()
    {
        $data = [['email' => 'mail'], ['email' => 'mail2']];
        $this->assertEquals([['email' => 'mail'], ['email' => 'mail2']], \Illuminate\Validation\ValidationData::extractDataFromPath(null, $data));

        $data = ['cat' => ['cat1' => ['name']], ['cat2' => ['name2']]];
        $this->assertEquals(['cat' => ['cat1' => ['name']]], \Illuminate\Validation\ValidationData::extractDataFromPath('cat.cat1', $data));

        $data = ['cat' => ['cat1' => ['name' => '1', 'price' => 1]], ['cat2' => ['name' => 2]]];
        $this->assertEquals(['cat' => ['cat1' => ['name' => '1']]], \Illuminate\Validation\ValidationData::extractDataFromPath('cat.cat1.name', $data));
    }

    public function testInlineMessagesMayUseAsteriskForEachRules()
    {
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

        $this->assertEquals($v->invalid(), [
            1 => ['name' => null],
            2 => ['name' => ''],
        ]);

        $v = new Validator($trans,
            [
                'name' => '',
            ],
            [
                'name' => 'required',
            ]);

        $this->assertEquals($v->invalid(), [
            'name' => '',
        ]);
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

        $this->assertEquals($v->valid(), [
            0 => ['name' => 'John'],
            3 => ['name' => 'Doe'],
        ]);

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

        $this->assertEquals($v->valid(), [
            'name' => 'Carlos',
            'gender' => 'male',
        ]);
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

    protected function getTranslator()
    {
        return m::mock('Illuminate\Contracts\Translation\Translator');
    }

    public function getIlluminateArrayTranslator()
    {
        return new \Illuminate\Translation\Translator(
            new \Illuminate\Translation\ArrayLoader, 'en'
        );
    }
}
