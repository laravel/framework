<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationUrlRuleTest extends TestCase
{
    private const ATTRIBUTE = 'my_url';
    private const ATTRIBUTE_REPLACED = 'my url';

    public function testBasic()
    {
        $this->fails(
            Rule::url(),
            'foo',
            ['The '.self::ATTRIBUTE_REPLACED.' field format is invalid.']
        );

        $this->passes(
            Rule::url(),
            'https://laravel.com'
        );

        $this->passes(
            Rule::url(),
            'http://laravel.com'
        );
    }

    public function testProtocols()
    {
        $this->fails(
            Rule::url()->protocols(['https']),
            'http://laravel.com',
            ['The '.self::ATTRIBUTE_REPLACED.' field format is invalid.']
        );

        $this->passes(
            Rule::url()->protocols(['https']),
            'https://laravel.com'
        );

        $this->passes(
            Rule::url()->protocols(['http', 'https']),
            'http://laravel.com'
        );
    }

    public function testActive()
    {
        $this->fails(
            Rule::url()->active(),
            'https://non-existent-domain-123456789.com',
            ['The '.self::ATTRIBUTE_REPLACED.' field format is invalid.']
        );

        $this->passes(
            Rule::url()->active(),
            'https://google.com'
        );
    }

    /**
     * @param  mixed  $rule
     * @param  string|array  $values
     * @param  array  $expectedMessages
     * @return void
     */
    protected function fails($rule, $values, $expectedMessages)
    {
        $this->assertValidationRules($rule, $values, false, $expectedMessages);
    }

    /**
     * @param  mixed  $rule
     * @param  string|array  $values
     * @param  bool  $expectToPass
     * @param  array  $expectedMessages
     * @return void
     */
    protected function assertValidationRules($rule, $values, $expectToPass, $expectedMessages = [])
    {
        $values = Arr::wrap($values);

        $translator = $this->getTranslator();

        foreach ($values as $value) {
            $v = new Validator(
                $translator,
                [self::ATTRIBUTE => $value],
                [self::ATTRIBUTE => is_object($rule) ? clone $rule : $rule]
            );

            // Mocking the container for the Validator
            $container = new \Illuminate\Container\Container;
            $container->singleton('validator', function () use ($translator) {
                return new \Illuminate\Validation\Factory($translator);
            });
            \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
            $v->setContainer($container);

            $this->assertSame($expectToPass, $v->passes(), 'Expected URL input '.$value.' to '.($expectToPass ? 'pass' : 'fail').'.');

            if (! $expectToPass) {
                $this->assertSame(
                    [self::ATTRIBUTE => $expectedMessages],
                    $v->messages()->toArray(),
                    'Expected different message for URL input '.$value
                );
            }
        }
    }

    /**
     * @param  mixed  $rule
     * @param  string|array  $values
     * @return void
     */
    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true);
    }

    protected function getTranslator()
    {
        $loader = new \Illuminate\Translation\ArrayLoader;
        $loader->addMessages('en', 'validation', [
            'url' => 'The :attribute field format is invalid.',
            'active_url' => 'The :attribute field format is invalid.',
        ]);

        return new \Illuminate\Translation\Translator($loader, 'en');
    }
}
