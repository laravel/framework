<?php

namespace Illuminate\Tests\Integration\Foundation\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\ProvidesExceptionSolutions;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\SolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Providers\MissingAppKeySolutionProvider;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\Solution;
use Illuminate\Foundation\Exceptions\Renderer\Solutions\SolutionProviderRepository;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Throwable;

class ErrorSolutionsTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=');
    }

    protected function defineRoutes($router)
    {
        $router->get('missing-key', fn () => throw new \Illuminate\Encryption\MissingAppKeyException());
        $router->get('no-solution', fn () => throw new RuntimeException('Some random error'));
        $router->get('has-solutions', fn () => throw new ExceptionWithSolutions());
    }

    #[WithConfig('app.debug', true)]
    public function testSolutionProviderRepositoryIsRegistered()
    {
        $this->assertTrue($this->app->bound(SolutionProviderRepository::class));
    }

    #[WithConfig('app.debug', false)]
    public function testSolutionProviderRepositoryIsNotRegisteredWhenDebugDisabled()
    {
        $this->assertFalse($this->app->bound(SolutionProviderRepository::class));
    }

    #[WithConfig('app.debug', true)]
    public function testSolutionsAreDisplayedForMissingAppKey()
    {
        $this->get('/missing-key')
            ->assertInternalServerError()
            ->assertSee('Suggested solutions')
            ->assertSee('Generate an application key');
    }

    #[WithConfig('app.debug', true)]
    public function testNoSolutionsSectionWhenNoSolutionsApply()
    {
        $this->get('/no-solution')
            ->assertInternalServerError()
            ->assertDontSee('Suggested solutions');
    }

    #[WithConfig('app.debug', true)]
    public function testExceptionImplementingProvidesExceptionSolutionsDisplaysSolutions()
    {
        $this->get('/has-solutions')
            ->assertInternalServerError()
            ->assertSee('Suggested solutions')
            ->assertSee('Custom solution title');
    }

    #[WithConfig('app.debug', true)]
    public function testDevelopersCanRegisterCustomProviders()
    {
        $repository = $this->app->make(SolutionProviderRepository::class);
        $repository->register([CustomSolutionProvider::class]);

        $this->assertContains(CustomSolutionProvider::class, $repository->getProviders());
    }

    #[WithConfig('app.debug', true)]
    public function testFrameworkDefaultProvidersAreRegistered()
    {
        $repository = $this->app->make(SolutionProviderRepository::class);

        $this->assertContains(MissingAppKeySolutionProvider::class, $repository->getProviders());
    }

    #[WithConfig('app.debug', true)]
    public function testCustomProvidersRegisteredOnHandlerAreUsedByRepository()
    {
        $handler = $this->app->make(ExceptionHandler::class);
        $handler->solutionProviders([CustomSolutionProvider::class]);

        $this->app->forgetInstance(SolutionProviderRepository::class);
        $repository = $this->app->make(SolutionProviderRepository::class);

        $this->assertContains(CustomSolutionProvider::class, $repository->getProviders());
        $this->assertContains(MissingAppKeySolutionProvider::class, $repository->getProviders());
    }

    #[WithConfig('app.debug', true)]
    public function testSolutionProviderRepositoryWalksExceptionChain()
    {
        $repository = new SolutionProviderRepository($this->app);
        $repository->register([CustomSolutionProvider::class]);

        $inner = new RuntimeException('Custom error');
        $outer = new RuntimeException('Wrapper', 0, $inner);

        $solutions = $repository->getSolutions($outer);

        $this->assertNotEmpty($solutions);
        $this->assertSame('Custom solution', $solutions[0]->title());
    }

    #[WithConfig('app.debug', true)]
    public function testSolutionWithLinks()
    {
        $solution = Solution::create('Title', 'Description')
            ->withLinks(['Docs' => 'https://laravel.com']);

        $this->assertSame('Title', $solution->title());
        $this->assertSame('Description', $solution->description());
        $this->assertSame(['Docs' => 'https://laravel.com'], $solution->links());
    }
}

class ExceptionWithSolutions extends RuntimeException implements ProvidesExceptionSolutions
{
    public function __construct()
    {
        parent::__construct('Exception with solutions');
    }

    public function getSolutions(): array
    {
        return [
            new Solution('Custom solution title', 'Custom solution description'),
        ];
    }
}

class CustomSolutionProvider implements SolutionProvider
{
    public function canSolve(Throwable $throwable): bool
    {
        return str_contains($throwable->getMessage(), 'Custom error');
    }

    public function getSolutions(Throwable $throwable): array
    {
        return [
            new Solution('Custom solution', 'Fix this custom error'),
        ];
    }
}
