<?php

use Mockery as m;
use Illuminate\Validation\Extension;

class ValidationExtensionOne extends Extension
{
    protected static $replacers = [
        'custom_validation_rule' => [':foo' => 'bar'],
    ];
    
    public function validateCustomValidationRule()
    {
        return true;
    }
}

class ValidationExtensionTwo extends Extension
{
    protected static $replacers = [
        'custom_validation_rule' => [':foo' => 'bar'],
    ];
    
    public function validateAnotherCustomRule()
    {
        return true;
    }
    
    public function replaceAnotherCustomRule($message, $attribute, $rule, $parameters)
    {
        return str_replace(':baz', 'qux', $message);
    }
}

class ValidationExtensionTest extends PHPUnit_Framework_TestCase
{
    public function testGetRulesReturnsCorrectRules()
    {
        $ve = new ValidationExtensionOne([]);
        $this->assertEquals(['custom_validation_rule'], $ve->getRules());
        
        $ve = new ValidationExtensionTwo([]);
        $this->assertEquals(['another_custom_rule'], $ve->getRules());
    }
    
    public function testGetReplacersReturnsCorrectReplacers()
    {
        $ve = new ValidationExtensionOne([]);
        $this->assertEquals(['custom_validation_rule'], $ve->getReplacers());
        
        $ve = new ValidationExtensionTwo([]);
        $this->assertEquals(['custom_validation_rule', 'another_custom_rule'], $ve->getReplacers());
    }
}
