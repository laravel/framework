<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\ComposerScripts;
use PHPUnit\Framework\TestCase;

class FoundationComposerScriptsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'], $_SERVER['LARAVEL_INSTALLER_DEFER_HOOKS']);
        putenv('LARAVEL_INSTALLER_DEFER_HOOKS');

        parent::tearDown();
    }

    public function testItSetsDeferredInstallerHookEnvironmentVariableFromEnv(): void
    {
        $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'] = '1';
        putenv('LARAVEL_INSTALLER_DEFER_HOOKS');

        ComposerScriptsTestProxy::setDeferredInstallerHookEnvironmentVariable();

        $this->assertSame('1', getenv('LARAVEL_INSTALLER_DEFER_HOOKS'));
    }

    public function testItSetsDeferredInstallerHookEnvironmentVariableFromServer(): void
    {
        $_SERVER['LARAVEL_INSTALLER_DEFER_HOOKS'] = '1';
        putenv('LARAVEL_INSTALLER_DEFER_HOOKS');

        ComposerScriptsTestProxy::setDeferredInstallerHookEnvironmentVariable();

        $this->assertSame('1', getenv('LARAVEL_INSTALLER_DEFER_HOOKS'));
    }

    public function testItDoesNotOverwriteExistingDeferredInstallerHookEnvironmentVariable(): void
    {
        $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'] = '1';
        putenv('LARAVEL_INSTALLER_DEFER_HOOKS=0');

        ComposerScriptsTestProxy::setDeferredInstallerHookEnvironmentVariable();

        $this->assertSame('0', getenv('LARAVEL_INSTALLER_DEFER_HOOKS'));
    }
}

class ComposerScriptsTestProxy extends ComposerScripts
{
    public static function setDeferredInstallerHookEnvironmentVariable(): void
    {
        parent::setDeferredInstallerHookEnvironmentVariable();
    }
}
