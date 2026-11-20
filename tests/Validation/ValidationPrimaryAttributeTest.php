<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationPrimaryAttributeTest extends TestCase
{
    public function test_it_preserves_the_first_matching_wildcard()
    {
        $validator = $this->validator(['items' => [['email' => 'a']]], [
            'items.*.email' => 'string',
            'items.0.*' => 'string',
        ]);

        $this->assertSame('items.*.email', $validator->getPrimaryAttribute('items.0.email'));
        $this->assertSame('missing', $validator->getPrimaryAttribute('missing'));
    }

    public function test_it_preserves_strict_matching_for_numeric_attributes()
    {
        $validator = $this->validator(['a'], ['*' => 'string']);

        $this->assertSame('*', $validator->getPrimaryAttribute(0));
        $this->assertSame('0', $validator->getPrimaryAttribute('0'));
    }

    public function test_it_refreshes_primary_attributes_when_rules_are_added()
    {
        $validator = $this->validator(['items' => [['email' => 'a']]], []);

        $this->assertSame('items.0.email', $validator->getPrimaryAttribute('items.0.email'));

        $validator->addRules(['items.*.email' => 'string']);

        $this->assertSame('items.*.email', $validator->getPrimaryAttribute('items.0.email'));
    }

    public function test_it_refreshes_primary_attributes_when_rules_are_replaced_or_appended()
    {
        $validator = $this->validator(['items' => [['email' => 'a', 'name' => 'b']]], []);

        $this->assertSame('items.0.email', $validator->getPrimaryAttribute('items.0.email'));

        $validator->setRules(['items.*.email' => 'string']);

        $this->assertSame('items.*.email', $validator->getPrimaryAttribute('items.0.email'));
        $this->assertSame('items.0.name', $validator->getPrimaryAttribute('items.0.name'));

        $validator->appendRules(['items.*.name' => 'string']);

        $this->assertSame('items.*.name', $validator->getPrimaryAttribute('items.0.name'));
    }

    public function test_it_refreshes_primary_attributes_when_data_changes()
    {
        $validator = $this->validator(['items' => [['email' => 'a']]], ['items.*.email' => 'string']);

        $this->assertSame('items.1.email', $validator->getPrimaryAttribute('items.1.email'));

        $validator->setData(['items' => [['email' => 'a'], ['email' => 'b']]]);

        $this->assertSame('items.*.email', $validator->getPrimaryAttribute('items.1.email'));
    }

    public function test_sometimes_preserves_its_wildcard_precedence_even_when_no_rules_are_added()
    {
        $validator = $this->validator(['items' => [['email' => 'a']]], ['items.*.email' => 'string']);

        $this->assertSame('items.*.email', $validator->getPrimaryAttribute('items.0.email'));

        $validator->sometimes('items.0.*', 'required', fn () => false);

        $this->assertSame('items.0.*', $validator->getPrimaryAttribute('items.0.email'));
    }

    public function test_cross_field_rules_still_resolve_nested_wildcards()
    {
        $validator = $this->validator(['groups' => [
            ['items' => [['email' => 'a', 'confirmation' => 'a']]],
            ['items' => [['email' => 'b', 'confirmation' => 'wrong']]],
        ]], ['groups.*.items.*.confirmation' => 'same:groups.*.items.*.email']);

        $this->assertFalse($validator->passes());
        $this->assertSame([
            'groups.1.items.0.confirmation' => ['Same' => ['groups.1.items.0.email']],
        ], $validator->failed());
    }

    private function validator(array $data, array $rules): Validator
    {
        return new class(new Translator(new ArrayLoader, 'en'), $data, $rules) extends Validator
        {
            public function getPrimaryAttribute($attribute)
            {
                return parent::getPrimaryAttribute($attribute);
            }
        };
    }
}
