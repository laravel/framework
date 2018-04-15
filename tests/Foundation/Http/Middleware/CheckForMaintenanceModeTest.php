<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;

class CheckForMaintenanceModeTest extends TestCase
{
    public function tearDown()
    {
        @unlink(MaintenanceApplication::MAINTENANCE_FILE);
    }

    public function testApplicationIsRunnning()
    {
        $middleware = new CheckForMaintenanceMode(new RunningApplication);
        $response = $middleware->handle(Request::create('/'), function ($request) {
            return Response::create('The application is running.');
        });

        $this->assertEquals('The application is running.', $response->getContent());
    }

    public function testApplicationIsInMaintenanceMode()
    {
        $this->expectException(MaintenanceModeException::class);
        $this->expectExceptionMessage(MaintenanceApplication::MAINTENANCE_MESSAGE);

        $middleware = new CheckForMaintenanceMode(new MaintenanceApplication);
        $middleware->handle(Request::create('/'), function () {
            return Response::create();
        });
    }
}

class MaintenanceApplication extends Application
{
    const MAINTENANCE_FILE = __DIR__.'/down';
    const MAINTENANCE_MESSAGE = 'Be right back!';

    public function getMaintenanceFilePath()
    {
        if (! file_exists($path = static::MAINTENANCE_FILE)) {
            file_put_contents($path, json_encode([
                'time' => time(),
                'message' => static::MAINTENANCE_MESSAGE,
                'retry' => null,
            ]));
        }

        return static::MAINTENANCE_FILE;
    }
}

class RunningApplication extends Application
{
    public function getMaintenanceFilePath()
    {
        return '/non-exists-directory/down';
    }
}
