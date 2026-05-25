<?php

namespace Illuminate\Tests\View;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use PHPUnit\Framework\TestCase;

class ViewStackTest extends TestCase
{
    protected string $tmpDir;
    protected Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir().'/laravel_stack_test_'.uniqid();
        mkdir($this->tmpDir);

        $resolver = new EngineResolver;
        $resolver->register('php', fn () => new PhpEngine(new Filesystem));

        $finder = new FileViewFinder(new Filesystem, [$this->tmpDir]);
        $finder->addExtension('php');

        $this->factory = new Factory($resolver, $finder, new Dispatcher);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir.'/*.php'));
        rmdir($this->tmpDir);
        parent::tearDown();
    }

    protected function makeView(string $name, string $content): void
    {
        file_put_contents($this->tmpDir.'/'.$name.'.php', $content);
    }

    public function testPushFromLayoutComponentIsRenderedIntoStack(): void
    {
        $this->makeView('layout', <<<'PHP'
<head><?php echo $__env->yieldPushContent('scripts'); ?></head>
<body>
<?php echo $slot; ?>
<?php $__env->startPush('scripts'); ?>pushed from layout<?php $__env->stopPush(); ?>
</body>
PHP);

        $this->makeView('welcome', <<<'PHP'
<?php $__env->startPush('scripts'); ?>pushed from view<?php $__env->stopPush(); ?>
<?php
$slot = 'hello';
echo $__env->make('layout', ['slot' => $slot])->render();
?>
PHP);

        $output = $this->factory->make('welcome')->render();

        $this->assertStringContainsString('pushed from view', $output);
        $this->assertStringContainsString('pushed from layout', $output);

        $headStart = strpos($output, '<head>');
        $headEnd = strpos($output, '</head>');
        $headContent = substr($output, $headStart, $headEnd - $headStart);

        $this->assertStringContainsString('pushed from view', $headContent);
        $this->assertStringContainsString('pushed from layout', $headContent);
    }
}
