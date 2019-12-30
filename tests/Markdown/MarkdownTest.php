<?php

namespace Illuminate\Tests\Markdown;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Application;
use Illuminate\Markdown\CommonMarkRenderer;
use Illuminate\Markdown\MarkdownServiceProvider;
use Illuminate\Markdown\ParsedownRenderer;
use Illuminate\Markdown\PhpMarkdownRenderer;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Extension\ExtensionInterface;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    public function testCanLocateAndRender()
    {
        $app = new Application();
        $app->register(new MarkdownServiceProvider($app));

        $markdown = $app->make(Markdown::class);
        $this->assertInstanceOf(Markdown::class, $markdown);
        $this->assertInstanceOf(CommonMarkRenderer::class, $markdown);

        $html = $markdown->render('# Hello World');
        $this->assertInstanceOf(Htmlable::class, $html);
        $this->assertSame('<h1>Hello World</h1>', $html->toHtml());
    }

    public function testResolvesCustomCommonMarkEnv()
    {
        $app = new Application();
        $app->register(new MarkdownServiceProvider($app));

        $app->singleton(EnvironmentInterface::class, function () {
            $environment = Environment::createCommonMarkEnvironment();

            $environment->addExtension(new class implements ExtensionInterface {
                public function register(ConfigurableEnvironmentInterface $environment)
                {
                    throw new Exception('Ext was resolved!');
                }
            });

            return $environment;
        });

        $markdown = $app->make(Markdown::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ext was resolved!');

        $markdown->render('foo');
    }

    public function rendererProvider()
    {
        return [
            [CommonMarkRenderer::class],
            [ParsedownRenderer::class],
            [PhpMarkdownRenderer::class],
        ];
    }

    /**
     * @dataProvider rendererProvider
     */
    public function testRender($markdown)
    {
        $renderer = $markdown::create(new Container());
        $this->assertSame('', $renderer->render('')->toHtml());
        $this->assertSame('<h2>Hello There!</h2>', $renderer->render('## Hello There!')->toHtml());
        $this->assertSame("<ul>\n<li>hello\n*world</li>\n</ul>", $renderer->render("* hello\n*world")->toHtml());
    }
}
