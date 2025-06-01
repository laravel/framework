<?php

namespace Tests\Foundation\Console;

use Illuminate\Foundation\Console\SchemaMakeCommand;
use Orchestra\Testbench\TestCase;

class SchemaMakeCommandIntegrationTest extends TestCase
{
    public function testModelInferenceFromSchemaName()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Use reflection to test the protected inferModelFromName method
        $reflectionClass = new \ReflectionClass($command);
        $method = $reflectionClass->getMethod('inferModelFromName');
        $method->setAccessible(true);

        // Test different schema name patterns
        $testCases = [
            'Tests\\Foundation\\Console\\TestModels\\TestUserSchema' => 'Tests\\Foundation\\Console\\TestModels\\TestUser',
            'Tests\\Foundation\\Console\\TestModels\\TestUserValidation' => 'Tests\\Foundation\\Console\\TestModels\\TestUser',
            'Tests\\Foundation\\Console\\TestModels\\TestUsersSchema' => 'Tests\\Foundation\\Console\\TestModels\\TestUser',
            'Tests\\Foundation\\Console\\TestModels\\TestUser' => 'Tests\\Foundation\\Console\\TestModels\\TestUser',
        ];

        foreach ($testCases as $schemaName => $expectedModel) {
            $result = $method->invoke($command, $schemaName);
            $this->assertEquals($expectedModel, $result, "Failed to infer '{$expectedModel}' from '{$schemaName}'");
        }
    }

    public function testBasicSchemaFallback()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Use reflection to test the protected getBasicSchema method
        $reflectionClass = new \ReflectionClass($command);
        $method = $reflectionClass->getMethod('getBasicSchema');
        $method->setAccessible(true);

        $schema = $method->invoke($command);

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('conditions', $schema);

        // Should have basic name and email fields
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);

        // Name field should be required string
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertContains('required', $schema['properties']['name']['rules']);

        // Email field should be required email
        $this->assertEquals('string', $schema['properties']['email']['type']);
        $this->assertContains('required', $schema['properties']['email']['rules']);
        $this->assertContains('email', $schema['properties']['email']['rules']);
    }

    public function testColumnTypeMapping()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Use reflection to test the protected mapColumnTypeToProperty method
        $reflectionClass = new \ReflectionClass($command);
        $method = $reflectionClass->getMethod('mapColumnTypeToProperty');
        $method->setAccessible(true);

        // Test various column type mappings
        $testCases = [
            'varchar' => ['type' => 'string'],
            'char' => ['type' => 'string'],
            'text' => ['type' => 'string'],
            'integer' => ['type' => 'integer'],
            'bigint' => ['type' => 'integer'],
            'boolean' => ['type' => 'boolean'],
            'json' => ['type' => 'object'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'decimal' => ['type' => 'number'],
            'float' => ['type' => 'number'],
        ];

        foreach ($testCases as $columnType => $expectedProperty) {
            $result = $method->invoke($command, $columnType, 'test_field');

            $this->assertEquals($expectedProperty['type'], $result['type'], "Failed to map column type '{$columnType}' to correct JSON type");

            if (isset($expectedProperty['format'])) {
                $this->assertEquals($expectedProperty['format'], $result['format'], "Failed to set correct format for column type '{$columnType}'");
            }
        }
    }

    public function testFieldRequirementLogic()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Use reflection to test the protected shouldFieldBeRequired method
        $reflectionClass = new \ReflectionClass($command);
        $method = $reflectionClass->getMethod('shouldFieldBeRequired');
        $method->setAccessible(true);

        // Test that certain fields are considered required
        $this->assertTrue($method->invoke($command, 'name', 'varchar'), 'Name field should be required');
        $this->assertTrue($method->invoke($command, 'email', 'varchar'), 'Email field should be required');
        $this->assertTrue($method->invoke($command, 'title', 'varchar'), 'Title field should be required');
        $this->assertTrue($method->invoke($command, 'username', 'varchar'), 'Username field should be required');

        // Test that other fields are not required by default
        $this->assertFalse($method->invoke($command, 'description', 'text'), 'Description field should not be required');
        $this->assertFalse($method->invoke($command, 'notes', 'text'), 'Notes field should not be required');
        $this->assertFalse($method->invoke($command, 'bio', 'text'), 'Bio field should not be required');
    }
}
