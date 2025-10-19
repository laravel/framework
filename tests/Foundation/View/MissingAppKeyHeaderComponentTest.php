<?php

namespace Illuminate\Tests\Foundation\View;

use PHPUnit\Framework\TestCase;

class MissingAppKeyHeaderComponentTest extends TestCase
{
    public function testMissingAppKeyHeaderComponentFileExists()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        
        $this->assertTrue(file_exists($componentPath), 'Missing app key header component file should exist');
        $this->assertTrue(is_readable($componentPath), 'Missing app key header component file should be readable');
    }

    public function testMissingAppKeyHeaderComponentContainsExpectedContent()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        $content = file_get_contents($componentPath);
        
        // Check for essential Blade directives
        $this->assertStringContainsString('@props', $content, 'Component should have @props directive');
        
        // Check for expected UI elements
        $this->assertStringContainsString('Application Key Missing', $content, 'Component should contain the main heading');
        $this->assertStringContainsString('Generate Application Key', $content, 'Component should contain generate button');
        $this->assertStringContainsString('Copy Command', $content, 'Component should contain copy button');
        
        // Check for JavaScript functions
        $this->assertStringContainsString('generateAppKey()', $content, 'Component should contain generateAppKey function');
        $this->assertStringContainsString('copyCommand()', $content, 'Component should contain copyCommand function');
        
        // Check for security features
        $this->assertStringContainsString('X-CSRF-TOKEN', $content, 'Component should use CSRF protection');
        $this->assertStringContainsString('/__laravel_generate_key', $content, 'Component should reference the correct endpoint');
    }

    public function testMissingAppKeyHeaderComponentHasProperStyling()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        $content = file_get_contents($componentPath);
        
        // Check for Tailwind CSS classes
        $this->assertStringContainsString('bg-blue-50', $content, 'Component should have blue background');
        $this->assertStringContainsString('border-blue-200', $content, 'Component should have blue border');
        $this->assertStringContainsString('text-blue-900', $content, 'Component should have blue text');
        $this->assertStringContainsString('bg-blue-600', $content, 'Component should have blue button background');
        $this->assertStringContainsString('hover:bg-blue-700', $content, 'Component should have hover effects');
    }

    public function testShowTemplateUsesConditionalRendering()
    {
        $showTemplatePath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/show.blade.php';
        $content = file_get_contents($showTemplatePath);
        
        // Check for conditional rendering
        $this->assertStringContainsString('MissingAppKeyException', $content, 'Show template should check for MissingAppKeyException');
        $this->assertStringContainsString('missing-app-key-header', $content, 'Show template should use custom header component');
        $this->assertStringContainsString('@if', $content, 'Show template should have conditional logic');
        $this->assertStringContainsString('@else', $content, 'Show template should have else clause');
    }

    public function testLayoutHasCsrfToken()
    {
        $layoutPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/layout.blade.php';
        $content = file_get_contents($layoutPath);
        
        // Check for CSRF token meta tag
        $this->assertStringContainsString('csrf-token', $content, 'Layout should include CSRF token meta tag');
        $this->assertStringContainsString('{{ csrf_token() }}', $content, 'Layout should generate CSRF token');
    }
}