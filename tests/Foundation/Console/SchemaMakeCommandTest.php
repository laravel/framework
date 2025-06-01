<?php

namespace Tests\Foundation\Console;

use Illuminate\Foundation\Console\SchemaMakeCommand;
use Orchestra\Testbench\TestCase;

class SchemaMakeCommandTest extends TestCase
{
    public function testColumnTypeMappingWorksCorrectly()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Test integer mapping
        $property = $command->mapColumnTypeToProperty('integer', 'age');
        $this->assertEquals('integer', $property['type']);
        $this->assertContains('integer', $property['rules']);

        // Test string mapping
        $property = $command->mapColumnTypeToProperty('varchar', 'name');
        $this->assertEquals('string', $property['type']);
        $this->assertContains('string', $property['rules']);

        // Test email detection
        $property = $command->mapColumnTypeToProperty('varchar', 'email');
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('email', $property['format']);
        $this->assertContains('email', $property['rules']);

        // Test URL detection
        $property = $command->mapColumnTypeToProperty('varchar', 'website_url');
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('uri', $property['format']);
        $this->assertContains('url', $property['rules']);

        // Test boolean mapping
        $property = $command->mapColumnTypeToProperty('boolean', 'is_active');
        $this->assertEquals('boolean', $property['type']);
        $this->assertContains('boolean', $property['rules']);

        // Test date mapping
        $property = $command->mapColumnTypeToProperty('datetime', 'created_at');
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('date-time', $property['format']);
        $this->assertContains('date', $property['rules']);

        // Test decimal mapping
        $property = $command->mapColumnTypeToProperty('decimal', 'price');
        $this->assertEquals('number', $property['type']);
        $this->assertContains('numeric', $property['rules']);

        // Test text mapping
        $property = $command->mapColumnTypeToProperty('text', 'description');
        $this->assertEquals('string', $property['type']);
        $this->assertContains('string', $property['rules']);

        // Test JSON mapping
        $property = $command->mapColumnTypeToProperty('json', 'metadata');
        $this->assertEquals('object', $property['type']);
        $this->assertContains('array', $property['rules']);
    }

    public function testShouldFieldBeRequiredLogic()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Test required fields
        $this->assertTrue($command->shouldFieldBeRequired('name', 'varchar'));
        $this->assertTrue($command->shouldFieldBeRequired('title', 'varchar'));
        $this->assertTrue($command->shouldFieldBeRequired('email', 'varchar'));
        $this->assertTrue($command->shouldFieldBeRequired('username', 'varchar'));

        // Test optional fields
        $this->assertFalse($command->shouldFieldBeRequired('description', 'text'));
        $this->assertFalse($command->shouldFieldBeRequired('bio', 'text'));
        $this->assertFalse($command->shouldFieldBeRequired('avatar', 'varchar'));
        $this->assertFalse($command->shouldFieldBeRequired('notes', 'text'));
        $this->assertFalse($command->shouldFieldBeRequired('comment', 'text'));
        $this->assertFalse($command->shouldFieldBeRequired('image', 'varchar'));

        // Test ID fields (should not be required in forms)
        $this->assertFalse($command->shouldFieldBeRequired('id', 'integer'));
        $this->assertFalse($command->shouldFieldBeRequired('user_id', 'integer'));
        $this->assertFalse($command->shouldFieldBeRequired('category_id', 'integer'));
        $this->assertFalse($command->shouldFieldBeRequired('parent_id', 'integer'));

        // Test default behavior (most fields should be nullable)
        $this->assertFalse($command->shouldFieldBeRequired('random_field', 'varchar'));
        $this->assertFalse($command->shouldFieldBeRequired('some_custom_field', 'text'));
    }

    public function testCastMappingWorksCorrectly()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Test integer cast
        $property = $command->buildPropertyFromCast('integer', 'age');
        $this->assertEquals('integer', $property['type']);
        $this->assertContains('integer', $property['rules']);
        $this->assertContains('nullable', $property['rules']);

        // Test boolean cast
        $property = $command->buildPropertyFromCast('boolean', 'is_active');
        $this->assertEquals('boolean', $property['type']);
        $this->assertContains('boolean', $property['rules']);

        // Test array cast
        $property = $command->buildPropertyFromCast('array', 'settings');
        $this->assertEquals('array', $property['type']);
        $this->assertContains('array', $property['rules']);

        // Test date cast
        $property = $command->buildPropertyFromCast('datetime', 'published_at');
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('date-time', $property['format']);
        $this->assertContains('date', $property['rules']);

        // Test decimal cast
        $property = $command->buildPropertyFromCast('decimal', 'price');
        $this->assertEquals('number', $property['type']);
        $this->assertContains('numeric', $property['rules']);
    }

    public function testRelationshipPropertyBuilding()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Test hasMany relationship
        $property = $command->buildRelationshipProperty('hasMany');
        $this->assertEquals('array', $property['type']);
        $this->assertArrayHasKey('items', $property);
        $this->assertEquals('object', $property['items']['type']);
        $this->assertContains('array', $property['rules']);
        $this->assertContains('nullable', $property['rules']);

        // Test belongsTo relationship
        $property = $command->buildRelationshipProperty('belongsTo');
        $this->assertEquals('object', $property['type']);
        $this->assertContains('array', $property['rules']);
        $this->assertContains('nullable', $property['rules']);

        // Test hasOne relationship
        $property = $command->buildRelationshipProperty('hasOne');
        $this->assertEquals('object', $property['type']);
        $this->assertContains('array', $property['rules']);
        $this->assertContains('nullable', $property['rules']);
    }

    public function testSpecialFieldDetection()
    {
        $command = new SchemaMakeCommand($this->app['files']);

        // Test email field variations
        $emailFields = ['email', 'user_email', 'contact_email'];
        foreach ($emailFields as $field) {
            $property = $command->mapColumnTypeToProperty('varchar', $field);
            $this->assertEquals('email', $property['format'], "Field '{$field}' should be detected as email");
            $this->assertContains('email', $property['rules']);
        }

        // Test URL field variations
        $urlFields = ['url', 'website_url', 'homepage_link'];
        foreach ($urlFields as $field) {
            $property = $command->mapColumnTypeToProperty('varchar', $field);
            $this->assertEquals('uri', $property['format'], "Field '{$field}' should be detected as URL");
            $this->assertContains('url', $property['rules']);
        }
    }
}
