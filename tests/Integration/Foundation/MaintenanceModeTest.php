<?php

namespace Illuminate\Tests\Integration\Foundation;

use DateTimeInterface;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;

class MaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            @unlink(storage_path('framework/down'));
        });

        parent::setUp();
    }

    public function testBasicMaintenanceModeResponse()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'refresh' => 60,
        ]));

        Route::get('/foo', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/foo');

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', '60');
        $response->assertHeader('Refresh', '60');
    }

    public function testMaintenanceModeCanHaveCustomStatus()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'status' => 200,
        ]));

        Route::get('/foo', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/foo');

        $response->assertStatus(200);
        $response->assertHeader('Retry-After', '60');
    }

    public function testMaintenanceModeCanHaveCustomTemplate()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'template' => 'Rendered Content',
        ]));

        Route::get('/foo', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/foo');

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', '60');
        $this->assertSame('Rendered Content', $response->original);
    }

    public function testMaintenanceModeCanRedirectWithBypassCookie()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'secret' => 'foo',
            'template' => 'Rendered Content',
        ]));

        Route::get('/foo', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/foo');

        $response->assertStatus(302);
        $response->assertCookie('laravel_maintenance');
    }

    public function testMaintenanceModeCanBeBypassedWithValidCookie()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'secret' => 'foo',
        ]));

        $cookie = MaintenanceModeBypassCookie::create('foo');

        Route::get('/test', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->withUnencryptedCookies([
            'laravel_maintenance' => $cookie->getValue(),
        ])->get('/test');

        $response->assertStatus(200);
        $this->assertSame('Hello World', $response->original);
    }

    public function testMaintenanceModeCanBeBypassedOnExcludedUrls()
    {
        $this->app->instance(PreventRequestsDuringMaintenance::class, new class($this->app) extends PreventRequestsDuringMaintenance
        {
            protected $except = ['/test'];
        });

        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
        ]));

        Route::get('/test', fn () => 'Hello World')->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/test');

        $response->assertStatus(200);
        $this->assertSame('Hello World', $response->original);
    }

    public function testMaintenanceModeCantBeBypassedWithInvalidCookie()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'secret' => 'foo',
        ]));

        $cookie = MaintenanceModeBypassCookie::create('test-key');

        Route::get('/test', function () {
            return 'Hello World';
        })->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->withUnencryptedCookies([
            'laravel_maintenance' => $cookie->getValue(),
        ])->get('/test');

        $response->assertStatus(503);
    }

    public function testCanCreateBypassCookies()
    {
        $cookie = MaintenanceModeBypassCookie::create('test-key');

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('laravel_maintenance', $cookie->getName());

        $this->assertTrue(MaintenanceModeBypassCookie::isValid($cookie->getValue(), 'test-key'));
        $this->assertFalse(MaintenanceModeBypassCookie::isValid($cookie->getValue(), 'wrong-key'));

        Carbon::setTestNow(now()->addMonths(6));
        $this->assertFalse(MaintenanceModeBypassCookie::isValid($cookie->getValue(), 'test-key'));
    }

    public function testDispatchEventWhenMaintenanceModeIsEnabled()
    {
        Event::fake();

        Event::assertNotDispatched(MaintenanceModeEnabled::class);
        $this->artisan(DownCommand::class);
        Event::assertDispatched(MaintenanceModeEnabled::class);
    }

    public function testDispatchEventWhenMaintenanceModeIsDisabled()
    {
        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => 60,
            'refresh' => 60,
        ]));

        Event::fake();

        Event::assertNotDispatched(MaintenanceModeDisabled::class);
        $this->artisan(UpCommand::class);
        Event::assertDispatched(MaintenanceModeDisabled::class);
    }

    #[DataProvider('retryAfterDatetimeProvider')]
    public function testMaintenanceModeRetryCanAcceptDatetime(string $datetime): void
    {
        Carbon::setTestNow('2023-01-01 00:00:00');

        $this->artisan(DownCommand::class, ['--retry' => $datetime]);

        $data = json_decode(file_get_contents(storage_path('framework/down')), true);

        $expectedDate = Carbon::parse($datetime)->format(DateTimeInterface::RFC7231);
        $this->assertSame($expectedDate, $data['retry']);

        Carbon::setTestNow();
    }

    public static function retryAfterDatetimeProvider(): array
    {
        return [
            'ISO 8601 format' => ['2023-01-08 00:00:00'],
            'natural language' => ['tomorrow 14:00'],
            'relative time' => ['+2 hours'],
        ];
    }

    public function testMaintenanceModeRetryWithHttpDateHeader(): void
    {
        $retryDate = Carbon::now()->addWeek();
        $expectedHeader = $retryDate->format(DateTimeInterface::RFC7231);

        file_put_contents(storage_path('framework/down'), json_encode([
            'retry' => $expectedHeader,
        ]));

        Route::get('/foo', fn () => 'Hello World')->middleware(PreventRequestsDuringMaintenance::class);

        $response = $this->get('/foo');

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', $expectedHeader);
    }

    public function testMaintenanceModeRetryWithInvalidDatetimeReturnsNull(): void
    {
        $this->artisan(DownCommand::class, ['--retry' => 'not-a-valid-date']);

        $data = json_decode(file_get_contents(storage_path('framework/down')), true);

        $this->assertNull($data['retry']);
    }

    public function testMaintenanceModeRetryWithAtTimestampNotation(): void
    {
        $futureTimestamp = time() + 3600;

        $this->artisan(DownCommand::class, ['--retry' => '@'.$futureTimestamp]);

        $data = json_decode(file_get_contents(storage_path('framework/down')), true);

        $expectedDate = Carbon::createFromTimestamp($futureTimestamp)->format(DateTimeInterface::RFC7231);
        $this->assertSame($expectedDate, $data['retry']);
    }

    public function testMaintenanceModeRespectsBootstrapConfiguredExcludedPaths()
    {
        PreventRequestsDuringMaintenance::except([
            '/api/*',
            '/webhooks/*',
        ]);
        $this->artisan(DownCommand::class);

        $data = json_decode(file_get_contents(storage_path('framework/down')), true);

        $this->assertSame([
            '/api/*',
            '/webhooks/*',
        ], $data['except']);
    }
}
