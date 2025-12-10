<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Contracts\Validation\ScopeAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationScopeAwareRuleTest extends TestCase
{
    public function testScopeAwareRuleReceivesSiblingData()
    {
        $data = [
            'products' => [
                ['price' => 100, 'discount' => 150],
                ['price' => 200, 'discount' => 50],
                ['price' => 300, 'discount' => null],
            ],
        ];

        $rules = [
            'products.*.price' => 'required|numeric',
            'products.*.discount' => ['nullable', 'numeric', new DiscountMustBeLessThanPrice()],
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'products.0.discount' => ['Discount cannot exceed the price.'],
        ], $v->getMessageBag()->toArray());
    }

    public function testScopeAwareRulePasses()
    {
        $data = [
            'products' => [
                ['price' => 100, 'discount' => 50],
                ['price' => 200, 'discount' => 100],
            ],
        ];

        $rules = [
            'products.*.price' => 'required|numeric',
            'products.*.discount' => ['nullable', 'numeric', new DiscountMustBeLessThanPrice()],
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertTrue($v->passes());
    }

    public function testScopeAwareRuleWithDeeplyNestedWildcards()
    {
        $data = [
            'orders' => [
                [
                    'items' => [
                        ['price' => 50, 'quantity' => 2, 'max_quantity' => 1],
                        ['price' => 30, 'quantity' => 1, 'max_quantity' => 5],
                    ],
                ],
                [
                    'items' => [
                        ['price' => 100, 'quantity' => 3, 'max_quantity' => 10],
                    ],
                ],
            ],
        ];

        $rules = [
            'orders.*.items.*.quantity' => ['required', 'integer', new QuantityMustNotExceedMax()],
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'orders.0.items.0.quantity' => ['Quantity cannot exceed max quantity.'],
        ], $v->getMessageBag()->toArray());
    }

    public function testScopeAwareRuleWithConditionalLogic()
    {
        $data = [
            'clients' => [
                ['name' => 'John', 'state' => 'CA', 'tax_id' => null],
                ['name' => 'Jane', 'state' => 'NY', 'tax_id' => null],
                ['name' => 'Bob', 'state' => 'CA', 'tax_id' => '123-456'],
            ],
        ];

        $rules = [
            'clients.*.name' => 'required|string',
            'clients.*.state' => 'required|string',
            'clients.*.tax_id' => new RequiredIfState('state', 'CA'),
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'clients.0.tax_id' => ['The clients.0.tax_id field is required when state is CA.'],
        ], $v->getMessageBag()->toArray());
    }

    public function testScopeAwareRuleCanAccessNestedSiblingData()
    {
        $data = [
            'orders' => [
                [
                    'type' => 'physical',
                    'shipping' => ['method' => 'express', 'address' => null],
                ],
                [
                    'type' => 'digital',
                    'shipping' => ['method' => 'none', 'address' => null],
                ],
                [
                    'type' => 'physical',
                    'shipping' => ['method' => 'standard', 'address' => '123 Main St'],
                ],
            ],
        ];

        $rules = [
            'orders.*.type' => 'required|in:physical,digital',
            'orders.*.shipping.address' => new RequiredForPhysicalOrder(),
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'orders.0.shipping.address' => ['Shipping address is required for physical orders.'],
        ], $v->getMessageBag()->toArray());
    }

    public function testScopeAwareRuleWithMultipleRulesOnSameAttribute()
    {
        $data = [
            'products' => [
                ['price' => 100, 'discount' => 150, 'type' => 'sale'],
                ['price' => 200, 'discount' => 50, 'type' => 'sale'],
                ['price' => 300, 'discount' => null, 'type' => 'regular'],
            ],
        ];

        $rules = [
            'products.*.price' => 'required|numeric',
            'products.*.discount' => [
                'nullable',
                'numeric',
                new DiscountMustBeLessThanPrice(),
            ],
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'products.0.discount' => ['Discount cannot exceed the price.'],
        ], $v->getMessageBag()->toArray());
    }

    public function testScopeAwareRuleWithoutWildcardReceivesFullData()
    {
        $data = [
            'price' => 100,
            'discount' => 150,
        ];

        $rules = [
            'price' => 'required|numeric',
            'discount' => ['nullable', 'numeric', new DiscountMustBeLessThanPrice()],
        ];

        $v = new Validator($this->getTranslator(), $data, $rules);

        $this->assertFalse($v->passes());
        $this->assertEquals([
            'discount' => ['Discount cannot exceed the price.'],
        ], $v->getMessageBag()->toArray());
    }

    protected function getTranslator()
    {
        return new Translator(
            new ArrayLoader, 'en'
        );
    }
}

class RequiredIfState implements ValidationRule, ScopeAwareRule
{
    protected array $scope = [];

    public function __construct(
        protected string $field,
        protected string $value
    ) {
    }

    public function setScope(array $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($this->scope[$this->field] ?? null) === $this->value && empty($value)) {
            $fail("The {$attribute} field is required when {$this->field} is {$this->value}.");
        }
    }
}

class RequiredForPhysicalOrder implements ValidationRule, ScopeAwareRule
{
    protected array $scope = [];

    public function setScope(array $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($this->scope['type'] ?? null) === 'physical' && empty($value)) {
            $fail('Shipping address is required for physical orders.');
        }
    }
}

class DiscountMustBeLessThanPrice implements ValidationRule, ScopeAwareRule
{
    protected array $scope = [];

    public function setScope(array $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && $value > ($this->scope['price'] ?? 0)) {
            $fail('Discount cannot exceed the price.');
        }
    }
}

class QuantityMustNotExceedMax implements ValidationRule, ScopeAwareRule
{
    protected array $scope = [];

    public function setScope(array $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value > ($this->scope['max_quantity'] ?? PHP_INT_MAX)) {
            $fail('Quantity cannot exceed max quantity.');
        }
    }
}
