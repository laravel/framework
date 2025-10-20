<?php

namespace Illuminate\Tests\Foundation\View;

use Orchestra\Testbench\TestCase;

class MissingAppKeyHeaderComponentTest extends TestCase
{
    public function test_missing_app_key_header_component_file_exists()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';

        $this->assertFileExists($componentPath);
        $this->assertFileIsReadable($componentPath);
    }

    public function test_missing_app_key_header_component_contains_expected_elements()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        $content = file_get_contents($componentPath);

        // Check for key UI elements
        $this->assertStringContainsString('@props([\'exception\'])', $content);
        $this->assertStringContainsString('Application Key Missing', $content);
        $this->assertStringContainsString('Generate Application Key', $content);
        $this->assertStringContainsString('Copy Command', $content);
        $this->assertStringContainsString('function generateAppKey()', $content);
        $this->assertStringContainsString('function copyCommand()', $content);
        $this->assertStringContainsString('/__laravel_generate_key', $content);
    }

    public function test_missing_app_key_header_component_has_proper_styling()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        $content = file_get_contents($componentPath);

        // Check for Tailwind classes
        $this->assertStringContainsString('bg-blue-', $content);
        $this->assertStringContainsString('dark:', $content);
        $this->assertStringContainsString('rounded-md', $content);
    }

    public function test_missing_app_key_header_component_has_csrf_token_reference()
    {
        $componentPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/missing-app-key-header.blade.php';
        $content = file_get_contents($componentPath);

        $this->assertStringContainsString('X-CSRF-TOKEN', $content);
        $this->assertStringContainsString('csrf-token', $content);
    }

    public function test_show_blade_template_uses_conditional_rendering()
    {
        $showPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/show.blade.php';
        $content = file_get_contents($showPath);

        $this->assertStringContainsString('Illuminate\Encryption\MissingAppKeyException', $content);
        $this->assertStringContainsString('missing-app-key-header', $content);
    }

    public function test_layout_blade_template_includes_csrf_meta_tag()
    {
        $layoutPath = __DIR__.'/../../../src/Illuminate/Foundation/resources/exceptions/renderer/components/layout.blade.php';
        $content = file_get_contents($layoutPath);

        $this->assertStringContainsString('<meta name="csrf-token"', $content);
        $this->assertStringContainsString('{{ csrf_token() }}', $content);
    }
}
