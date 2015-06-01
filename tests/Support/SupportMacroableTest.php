<?php

class SupportMacroableTest extends PHPUnit_Framework_TestCase
{
    private $macroable;

    public function setUp()
    {
        $this->macroable = $this->createObjectForTrait();
    }

    private function createObjectForTrait()
    {
        $traitName = 'Illuminate\Support\Traits\Macroable';

        return $this->getObjectForTrait($traitName);
    }

    public function testRegisterMacro()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () { return 'Taylor'; });
        $this->assertEquals('Taylor', $macroable::{__CLASS__}());
    }

    public function testRegisterMacroAndCallWithoutStatic()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () { return 'Taylor'; });
        $this->assertEquals('Taylor', $macroable->{__CLASS__}());
    }

    public function testWhenCallingMacroClosureIsBoundToObject()
    {
        TestMacroable::macro('tryInstance', function () { return $this->protectedVariable; });
        TestMacroable::macro('tryStatic', function () { return static::getProtectedStatic(); });
        $instance = new TestMacroable;

        $result = $instance->tryInstance();
        $this->assertEquals('instance', $result);

        $result = TestMacroable::tryStatic();
        $this->assertEquals('static', $result);
    }
}

class TestMacroable
{
    use Illuminate\Support\Traits\Macroable;
    protected $protectedVariable = 'instance';
    protected static function getProtectedStatic()
    {
        return 'static';
    }
}
