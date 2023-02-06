<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\DocsCommand;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
            'https://laravel.com/docs/8.x/index.json' => Http::response(file_get_contents(__DIR__.'/fixtures/docs.json')),
        ]);

        $this->app[Kernel::class]->registerCommand($this->command());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('ARTISAN_DOCS_ASK_STRATEGY');
        putenv('ARTISAN_DOCS_OPEN_STRATEGY');
    }

    public function testItCanOpenTheLaravelDocumentation(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', '')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x');
    }

    public function testItCanSpecifyAutocompleteInOriginalCasing(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'Laravel Dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/dusk');
    }

    public function testItCanSpecifyAutocompleteInLowerCasing(): void
    {
        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/dusk');
    }

    public function testItMatchesSectionsThatStartWithInput()
    {
        $this->artisan('docs el-col uni')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections#method-unique')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent-collections#method-unique');
    }

    public function testItMatchesSectionsWithFuzzyMatching()
    {
        $this->artisan('docs el-col qery')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections#method-toquery')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent-collections#method-toquery');
    }

    public function testItCanProvidePageToVisit(): void
    {
        $this->artisan('docs eloquent\ collections')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent-collections');
    }

    public function testItCanUseHyphensInsteadOfEscapingSpaces(): void
    {
        $this->artisan('docs eloquent-collections')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent-collections');
    }

    public function testItHasMinimumScoreToMatch(): void
    {
        $this->artisan('docs zag')
            ->expectsOutputToContain('Unable to determine the page you are trying to visit.')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x');
    }

    public function testItMinimumScoreAccountsForInputLength(): void
    {
        $this->artisan('docs z')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/localization')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/localization');
    }

    public function testItCanUseCustomAskStrategy()
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/always-dusk-ask-strategy.php');

        $this->artisan('docs')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/dusk');
    }

    public function testItFallsbackToAutocompleteWhenAskStrategyContainsBadSyntax(): void
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/bad-syntax-strategy.php');

        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/dusk');
    }

    public function testItFallsbackToAutocompleteWithBadAskStrategyReturnValue(): void
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/bad-return-strategy.php');

        $this->artisan('docs')
            ->expectsQuestion('Which page would you like to open?', 'laravel dusk')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/dusk')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/dusk');
    }

    public function testItCatchesAndHandlesProcessInterruptExceptionsInAskStrategies()
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/process-interrupt-strategy.php');

        $this->artisan('docs')->assertExitCode(130);
    }

    public function testItBubblesUpAskStrategyExceptions()
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/exception-throwing-strategy.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('strategy failed');

        $this->artisan('docs');
    }

    public function testItBubblesUpNonProcessInterruptExceptionsInAskStratgies()
    {
        putenv('ARTISAN_DOCS_ASK_STRATEGY='.__DIR__.'/fixtures/process-failure-strategy.php');

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

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent');
    }

    public function testItCanGuessTheRequestedPageWhenItIsContainedSomewhereInThePageTitle()
    {
        $this->artisan('docs quent')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent');
    }

    public function testItCanGuessTheWithTopAndTailMatching()
    {
        $this->artisan('docs elo-col')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/eloquent-collections')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/eloquent-collections');
    }

    public function testItCanSpecifyCustomOpenCommandsViaEnvVariables()
    {
        $GLOBALS['open-strategy-output-path'] = __DIR__.'/output.txt';
        putenv('ARTISAN_DOCS_OPEN_STRATEGY='.__DIR__.'/fixtures/open-strategy.php');
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

    public function testItHandlesBadSyntaxInOpeners()
    {
        putenv('ARTISAN_DOCS_OPEN_STRATEGY='.__DIR__.'/fixtures/bad-syntax-strategy.php');
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null));

        $this->artisan('docs installation')
            ->expectsOutputToContain('Unable to open the URL with your custom strategy. You will need to open it yourself.')
            ->assertSuccessful();
    }

    public function testItHandlesBadReturnTypesInOpeners()
    {
        putenv('ARTISAN_DOCS_OPEN_STRATEGY='.__DIR__.'/fixtures/bad-return-strategy.php');
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

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x?q=here%20is%20my%20search%20term%20for%20the%20laravel%20website');

        $_SERVER['argv'] = $argCache;
    }

    public function testUnknownSystemNotifiedToOpenManualy()
    {
        $this->app[Kernel::class]->registerCommand($this->command()->setUrlOpener(null)->setSystemOsFamily('Laravel OS'));

        $this->artisan('docs validation')
            ->expectsOutputToContain('Unable to open the URL on your system. You will need to open it yourself or create a custom opener for your system.')
            ->assertSuccessful();
    }

    public function testGuessedMatchesThatDirectlyContainTheGivenStringRankHigerThanArbitraryMatches()
    {
        $this->artisan('docs ora')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/filesystem')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/filesystem');
    }

    public function testItHandlesPoorSpelling()
    {
        $this->artisan('docs vewis')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x/views')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x/views');
    }

    public function testItHandlesNoInteractionOption()
    {
        $this->artisan('docs -n')
            ->expectsOutputToContain('Opening the docs to: https://laravel.com/docs/8.x')
            ->assertSuccessful();

        $this->assertSame($this->openedUrl, 'https://laravel.com/docs/8.x');
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
