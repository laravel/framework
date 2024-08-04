<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\PhpProcess;
use Throwable;

class ExceptionHandlerTest extends TestCase
{
    /**
     * Resolve application HTTP exception handler.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'Illuminate\Foundation\Exceptions\Handler');
    }

    public function testItRendersAuthorizationExceptions()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(403)
            ->assertSeeText('expected message');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItDoesntReportExceptionsWithShouldntReportInterface()
    {
        Config::set('app.debug', true);
        $reported = [];
        $this->app[ExceptionHandler::class]->reportable(function (Throwable $e) use (&$reported) {
            $reported[] = $e;
        });

        $exception = new class extends \Exception implements ShouldntReport, Responsable
        {
            public function toResponse($request)
            {
                return response('shouldnt report', 500);
            }
        };

        Route::get('test-route', fn () => throw $exception);

        $this->getJson('test-route')
            ->assertStatus(500)
            ->assertSee('shouldnt report');

        $this->assertEquals([], $reported);
    }

    public function testItRendersAuthorizationExceptionsWithCustomStatusCode()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->withStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusCodeTextWhenNoMessageIsSet()
    {
        Route::get('test-route', fn () => Response::denyWithStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'Not Found',
            ]);

        Route::get('test-route', fn () => Response::denyWithStatus(418)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusButWithoutResponse()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(418));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItHasFallbackErrorMessageForUnknownStatusCodes()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(399));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(399)
            ->assertSeeText('Whoops, looks like something went wrong.');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(399)
            ->assertExactJson([
                'message' => 'Whoops, looks like something went wrong.',
            ]);
    }

    public function testItReturns400CodeOnMalformedRequests()
    {
        // HTTP request...
        $this->post('test-route', ['_method' => '__construct'])
            ->assertStatus(400)
            ->assertSeeText('Bad Request'); // see https://github.com/symfony/symfony/blob/1d439995eb6d780531b97094ff5fa43e345fc42e/src/Symfony/Component/ErrorHandler/Resources/views/error.html.php#L12

        // JSON request...
        $this->postJson('test-route', ['_method' => '__construct'])
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'Bad request.',
            ]);
    }

    #[DataProvider('exitCodesProvider')]
    public function testItReturnsNonZeroExitCodesForUncaughtExceptions($providers, $successful)
    {
        $basePath = static::applicationBasePath();
        $providers = json_encode($providers, true);

        $process = new PhpProcess(<<<EOF
<?php

require 'vendor/autoload.php';

\$laravel = Orchestra\Testbench\Foundation\Application::create(basePath: '$basePath', options: ['extra' => ['providers' => $providers]]);
\$laravel->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'Illuminate\Foundation\Exceptions\Handler');

\$kernel = \$laravel[Illuminate\Contracts\Console\Kernel::class];

return \$kernel->call('throw-exception-command');
EOF, __DIR__.'/../../../', ['APP_RUNNING_IN_CONSOLE' => true]);

        $process->run();

        $this->assertSame($successful, $process->isSuccessful());
    }

    public static function exitCodesProvider()
    {
        yield 'Throw exception' => [[Fixtures\Providers\ThrowUncaughtExceptionServiceProvider::class], false];
        yield 'Do not throw exception' => [[Fixtures\Providers\ThrowExceptionServiceProvider::class], true];
    }

    public function test_it_handles_malformed_error_views_in_production()
    {
        Config::set('view.paths', [__DIR__.'/Fixtures/MalformedErrorViews']);
        Config::set('app.debug', false);
        $reported = [];
        $this->app[ExceptionHandler::class]->reportable(function (Throwable $e) use (&$reported) {
            $reported[] = $e;
        });

        try {
            $response = $this->get('foo');
        } catch (Throwable) {
            $response ??= null;
        }

        $this->assertCount(1, $reported);
        $this->assertSame('Undefined variable $foo (View: '.__DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'MalformedErrorViews'.DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR.'404.blade.php)', $reported[0]->getMessage());
        $this->assertNotNull($response);
        $response->assertStatus(404);
    }

    public function test_it_handles_malformed_error_views_in_development()
    {
        Config::set('view.paths', [__DIR__.'/Fixtures/MalformedErrorViews']);
        Config::set('app.debug', true);
        $reported = [];
        $this->app[ExceptionHandler::class]->reportable(function (Throwable $e) use (&$reported) {
            $reported[] = $e;
        });

        try {
            $response = $this->get('foo');
        } catch (Throwable) {
            $response ??= null;
        }

        $this->assertCount(1, $reported);
        $this->assertSame('Undefined variable $foo (View: '.__DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'MalformedErrorViews'.DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR.'404.blade.php)', $reported[0]->getMessage());
        $this->assertNotNull($response);
        $response->assertStatus(500);
    }
}
