<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationsTest extends TestCase
{
    protected $traitObject;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->traitObject = $this->getMockForTrait(DatabaseMigrations::class, [], '', true, true, true, [
            'artisan',
            'beforeApplicationDestroyed',
        ]);

        $kernelObj = \Mockery::mock();
        $kernelObj->shouldReceive('setArtisan')
            ->with(null);

        $this->traitObject->app = [
            Kernel::class => $kernelObj,
        ];
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

    public function testRefreshTestDatabaseDefault()
    {
        $this->traitObject
            ->expects($this->exactly(1))
            ->method('artisan')
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => false,
                '--seed' => false,
            ]);

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('runDatabaseMigrations');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }

    public function testRefreshTestDatabaseWithDropViewsOption()
    {
        $this->traitObject->dropViews = true;

        $this->traitObject
            ->expects($this->exactly(1))
            ->method('artisan')
            ->with('migrate:fresh', [
                '--drop-views' => true,
                '--drop-types' => false,
                '--seed' => false,
            ]);

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('runDatabaseMigrations');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }

    public function testRefreshTestDatabaseWithDropTypesOption()
    {
        $this->traitObject->dropTypes = true;

        $this->traitObject
            ->expects($this->exactly(1))
            ->method('artisan')
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => true,
                '--seed' => false,
            ]);

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('runDatabaseMigrations');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }
}
