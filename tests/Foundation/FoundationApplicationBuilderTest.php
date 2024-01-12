<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FoundationApplicationBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        unset($_ENV['APP_BASE_PATH']);

        parent::tearDown();
    }

    public function testBaseDirectoryWithArg()
    {
        $_ENV['APP_BASE_PATH'] = __DIR__.'/as-env';

        $app = Application::configure(__DIR__.'/as-arg')->create();

        $this->assertSame(__DIR__.'/as-arg', $app->basePath());
    }

    public function testBaseDirectoryWithEnv()
    {
        $_ENV['APP_BASE_PATH'] = __DIR__.'/as-env';

        $app = Application::configure()->create();

        $this->assertSame(__DIR__.'/as-env', $app->basePath());
    }

    public function testBaseDirectoryWithComposer()
    {
        $app = Application::configure()->create();

        $this->assertSame(dirname(__DIR__, 2), $app->basePath());
    }
}
