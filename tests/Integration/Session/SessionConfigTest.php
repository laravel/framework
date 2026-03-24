<?php

namespace Illuminate\Tests\Integration\Session;

use Orchestra\Testbench\TestCase;

class SessionConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('SESSION_COOKIE');
        $this->setAppName('laravel');
        parent::tearDown();
    }

    public function testDefaultSessionCookieNameRespectsAppNameAndStripsInvalidCharacters()
    {
        $this->setAppName('[LOCAL] My Admin');
        $config = require __DIR__.'/../../../config/session.php';

        $this->assertSame('l_o_c_a_l_my_admin_session', $config['cookie']);
    }

    public function testDefaultSessionCookieNameMaintainsSnakeCase()
    {
        $this->setAppName('My App');
        $config = require __DIR__.'/../../../config/session.php';

        $this->assertSame('my_app_session', $config['cookie']);
    }

    protected function setAppName($name)
    {
        $_ENV['APP_NAME'] = $name;
        $_SERVER['APP_NAME'] = $name;
        putenv('APP_NAME='.$name);
    }
}
