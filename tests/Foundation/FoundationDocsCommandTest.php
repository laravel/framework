<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\DocsCommand;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[WithConfig('cache.default', 'array')]
class FoundationDocsCommandTest extends TestCase
{
    /**
     * The URL opened by the command.
     *
     * @var string|null
     */
    protected $openedUrl;

    /**
     * The command registered to the container.
     *
     * @var \Illuminate\Foundation\Console\DocsCommand
     */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests()->fake([
            'https://laravel.com/docs/8.x/index.json' => Http::response(file_get_contents(__DIR__.'/Fixtures/docs.json')),
        ]);

        $this->app[Kernel::class]->registerCommand($this->command());
    }

    public function testItCanOpenTheLaravelDocumentation(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', '')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/installation')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/installation', $this->openedUrl);
    }

    public function testItCanSpecifyAutocompleteInOriginalCasing(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'Laravel Dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItCanSpecifyAutocompleteInLowerCasing(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/dusk', $this->openedUrl);
    }

    public function testItMatchesSectionsThatStartWithInput()
    {
        $this->artisan('docs el-col uni')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections#method-unique')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent-collections#method-unique', $this->openedUrl);
    }

    public function testItMatchesSectionsWithFuzzyMatching()
    {
        $this->artisan('docs el-col qery')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections#method-toquery')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent-collections#method-toquery', $this->openedUrl);
    }

    public function testItCanProvidePageToVisit(): void
    {
        $this->artisan('docs eloquent\ collections')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    public function testItCanUseHyphensInsteadOfEscapingSpaces(): void
    {
        $this->artisan('docs eloquent-collections')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    public function testItHasMinimumScoreToMatch(): void
    {
        $this->artisan('docs zag')
            ->expectsOutputToContain('Unable to determine the page you are trying to visit.')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x', $this->openedUrl);
    }

    public function testItMinimumScoreAccountsForInputLength(): void
    {
        $this->artisan('docs z')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/localization')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/localization', $this->openedUrl);
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/always-dusk-ask-strategy.php')]
    public function testItCanUseCustomAskStrategy()
    {
        $this->artisan('docs')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/dusk', $this->openedUrl);
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/bad-syntax-strategy.php')]
    public function testItFallsbackToAutocompleteWhenAskStrategyContainsBadSyntax(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/dusk', $this->openedUrl);
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/bad-return-strategy.php')]
    public function testItFallsbackToAutocompleteWithBadAskStrategyReturnValue(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/dusk', $this->openedUrl);
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/process-interrupt-strategy.php')]
    public function testItCatchesAndHandlesProcessInterruptExceptionsInAskStrategies()
    {
        $this->artisan('docs')->assertExitCode(130);
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/exception-throwing-strategy.php')]
    public function testItBubblesUpAskStrategyExceptions()
    {
        $this->expectExceptionObject(new RuntimeException('strategy failed'));

        $this->artisan('docs');
    }

    #[WithEnv('ARTISAN_DOCS_ASK_STRATEGY', __DIR__.'/Fixtures/process-failure-strategy.php')]
    public function testItBubblesUpNonProcessInterruptExceptionsInAskStrategies()
    {
        $this->expectException(ProcessFailedException::class);

        if (PHP_OS_FAMILY === 'Windows') {
            $this->expectExceptionMessage('The command "expected-command" failed.

Exit Code: 1(General error)

Working directory: expected-working-directory');
        } else {
            $this->expectExceptionMessage('The command "\'expected-command\'" failed.

Exit Code: 1(General error)

Working directory: expected-working-directory');
        }

        $this->artisan('docs');
    }

    public function testItCanGuessTheRequestedPageWhenItIsTheStartOfAPageTitle()
    {
        $this->artisan('docs elo')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent', $this->openedUrl);
    }

    public function testItCanGuessTheRequestedPageWhenItIsContainedSomewhereInThePageTitle()
    {
        $this->artisan('docs quent')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent', $this->openedUrl);
    }

    public function testItCanGuessTheWithTopAndTailMatching()
    {
        $this->artisan('docs elo-col')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/eloquent-collections', $this->openedUrl);
    }

    #[WithEnv('ARTISAN_DOCS_OPEN_STRATEGY', __DIR__.'/Fixtures/open-strategy.php')]
    public function testItCanSpecifyCustomOpenCommandsViaEnvVariables()
    {
        $GLOBALS['open-strategy-output-path'] = __DIR__.'/output.txt';
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        @unlink($GLOBALS['open-strategy-output-path']);

        $this->artisan('docs installation')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/installation')
            ->assertSuccessful();

        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertSame('"https://laravel.com/docs/8.x/installation?expected-query=1"', trim(file_get_contents($GLOBALS['open-strategy-output-path'])));
        } else {
            $this->assertSame('https://laravel.com/docs/8.x/installation?expected-query=1', trim(file_get_contents($GLOBALS['open-strategy-output-path'])));
        }

        @unlink($GLOBALS['open-strategy-output-path']);
        unset($GLOBALS['open-strategy-output-path']);
    }

    #[WithEnv('ARTISAN_DOCS_OPEN_STRATEGY', __DIR__.'/Fixtures/bad-syntax-strategy.php')]
    public function testItHandlesBadSyntaxInOpeners()
    {
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        $this->artisan('docs installation')
            ->expectsOutputToContain('Unable to open the URL with your custom strategy. You will need to open it yourself.')
            ->assertSuccessful();
    }

    #[WithEnv('ARTISAN_DOCS_OPEN_STRATEGY', __DIR__.'/Fixtures/bad-return-strategy.php')]
    public function testItHandlesBadReturnTypesInOpeners()
    {
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        $this->artisan('docs installation')
            ->expectsOutputToContain('Unable to open the URL with your custom strategy. You will need to open it yourself.')
            ->assertSuccessful();
    }

    public function testItCanPerformSearchAgainstLaravelDotCom()
    {
        $argCache = $_SERVER['argv'];
        $_SERVER['argv'] = explode(' ', 'artisan docs -- here is my search term for the laravel website');
        $this->app[Kernel::class]->registerCommand($this->command());

        $this->artisan('docs -- here is my search term for the laravel website')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x?q=here%20is%20my%20search%20term%20for%20the%20laravel%20website')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x?q=here%20is%20my%20search%20term%20for%20the%20laravel%20website', $this->openedUrl);

        $_SERVER['argv'] = $argCache;
    }

    public function testUnknownSystemNotifiedToOpenManually()
    {
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null)->setSystemOsFamily('Laravel OS'));

        $this->artisan('docs validation')
            ->expectsOutputToContain('Unable to open the URL on your system. You will need to open it yourself or create a custom opener for your system.')
            ->assertSuccessful();
    }

    public function testGuessedMatchesThatDirectlyContainTheGivenStringRankHigherThanArbitraryMatches()
    {
        $this->artisan('docs ora')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/filesystem')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/filesystem', $this->openedUrl);
    }

    public function testItHandlesPoorSpelling()
    {
        $this->artisan('docs vewis')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/views')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x/views', $this->openedUrl);
    }

    public function testItHandlesNoInteractionOption()
    {
        $this->artisan('docs -n')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x')
            ->assertSuccessful();

        $this->assertSame('https://laravel.com/docs/8.x', $this->openedUrl);
    }

    public function testCanGetHelpWithoutInstantiatingDependencies()
    {
        $help = (new DocsCommand())->getHelp();
        $this->stringContains('php artisan docs', $help);
    }

    protected function command()
    {
        $this->app->forgetInstance(DocsCommand::class);

        return $this->app->make(DocsCommand::class)
            ->setVersion('8.30.12')
            ->setUrlOpener(function ($url) {
                $this->openedUrl = $url;
            });
    }
}
