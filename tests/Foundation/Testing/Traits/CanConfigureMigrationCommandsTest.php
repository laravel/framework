<?php

namespace Illuminate\Tests\Foundation\Testing\Traits;

use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use PHPUnit\Framework\TestCase;

class CanConfigureMigrationCommandsTest extends TestCase
{
    protected $traitObject;

    protected function setup(): void
    {
        $this->traitObject = $this->getObjectForTrait(CanConfigureMigrationCommands::class);
    }

    private function __reflectAndSetupAccessibleForProtectedTraitMethod($methodName)
    {
        $migrateFreshUsingReflection = new \ReflectionMethod(
            get_class($this->traitObject),
            $methodName
        );

        $migrateFreshUsingReflection->setAccessible(true);

        return $migrateFreshUsingReflection;
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
