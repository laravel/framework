<?php

namespace Illuminate\Tests\Foundation\Testing\Traits;

use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CanConfigureMigrationCommandsTest extends TestCase
{
    protected $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new CanConfigureMigrationCommandsTestMockClass();
    }

    private function __reflectAndSetupAccessibleForProtectedTraitMethod($methodName)
    {
        return new ReflectionMethod(
            get_class($this->traitObject),
            $methodName
        );
    }

    public function testMigrateFreshUsingDefault(): void
    {
        $migrateFreshUsingReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('migrateFreshUsing');

        $expected = [
            '--drop-views' => false,
            '--drop-types' => false,
            '--seed' => false,
        ];

        $this->assertEquals($expected, $migrateFreshUsingReflection->invoke($this->traitObject));
    }

    public function testMigrateFreshUsingWithPropertySets(): void
    {
        $migrateFreshUsingReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('migrateFreshUsing');

        $expected = [
            '--drop-views' => true,
            '--drop-types' => false,
            '--seed' => false,
        ];

        $this->traitObject->dropViews = true;

        $this->assertEquals($expected, $migrateFreshUsingReflection->invoke($this->traitObject));

        $expected = [
            '--drop-views' => false,
            '--drop-types' => true,
            '--seed' => false,
        ];

        $this->traitObject->dropViews = false;
        $this->traitObject->dropTypes = true;

        $this->assertEquals($expected, $migrateFreshUsingReflection->invoke($this->traitObject));

        $expected = [
            '--drop-views' => true,
            '--drop-types' => true,
            '--seed' => false,
        ];

        $this->traitObject->dropViews = true;
        $this->traitObject->dropTypes = true;

        $this->assertEquals($expected, $migrateFreshUsingReflection->invoke($this->traitObject));
    }
}

class CanConfigureMigrationCommandsTestMockClass
{
    use CanConfigureMigrationCommands;

    public $dropViews = false;

    public $dropTypes = false;
}
