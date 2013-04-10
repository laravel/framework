<?php

use Mockery as m;
use Illuminate\View\Compilers\BladeCompiler;

class ViewBladeCompilerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(false);
		$this->assertTrue($compiler->isExpired('foo'));
	}


	public function testIsExpiredReturnsTrueIfCachePathIsNull()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), null);
		$files->shouldReceive('exists')->never();
		$this->assertTrue($compiler->isExpired('foo'));
	}


	public function testIsExpiredReturnsTrueWhenModificationTimesWarrant()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(true);
		$files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
		$files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(0);
		$this->assertTrue($compiler->isExpired('foo'));
	}


	public function testCompilePathIsProperlyCreated()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals(__DIR__.'/'.md5('foo'), $compiler->getCompiledPath('foo'));
	}


	public function testCompileCompilesFileAndReturnsContents()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
		$files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
		$files->shouldReceive('put')->once()->with(__DIR__.'/'.md5('foo'), 'Hello World');
		$compiler->compile('foo');
	}


	public function testCompileDoesntStoreFilesWhenCachePathIsNull()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), null);
		$files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
		$files->shouldReceive('put')->never();
		$compiler->compile('foo');
	}


	public function testEchosAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{$name}}}'));
		$this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{$name}}'));
		$this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{ $name }}'));
		$this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{ 
			$name
		}}'));
	}


	public function testExtendsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@extends(\'foo\')
test';
		$expected = "test\r\n".'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
		$this->assertEquals($expected, $compiler->compileString($string));


		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@extends(name(foo))
test';
		$expected = "test\r\n".'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testCommentsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '{{--this is a comment--}}';
		$expected = '<?php /* this is a comment */ ?>';
		$this->assertEquals($expected, $compiler->compileString($string));


		$string = '{{--
this is a comment
--}}';
		$expected = '<?php /* 
this is a comment
 */ ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testIfStatementsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@if (name(foo(bar)))
breeze
@endif';
		$expected = '<?php if (name(foo(bar))): ?>
breeze
<?php endif; ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testElseStatementsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@if (name(foo(bar)))
breeze
@else
boom
@endif';
		$expected = '<?php if (name(foo(bar))): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testElseIfStatementsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@if (name(foo(bar)))
breeze
@elseif (boom(breeze))
boom
@endif';
		$expected = '<?php if (name(foo(bar))): ?>
breeze
<?php elseif (boom(breeze)): ?>
boom
<?php endif; ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testUnlessStatementsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$string = '@unless (name(foo(bar)))
breeze
@endunless';
		$expected = '<?php if ( ! (name(foo(bar)))): ?>
breeze
<?php endif; ?>';
		$this->assertEquals($expected, $compiler->compileString($string));
	}


	public function testIncludesAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(\'foo\')'));
		$this->assertEquals('<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(name(foo))'));
	}


	public function testShowEachAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $compiler->compileString('@each(\'foo\', \'bar\')'));
		$this->assertEquals('<?php echo $__env->renderEach(name(foo)); ?>', $compiler->compileString('@each(name(foo))'));
	}


	public function testYieldsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo $__env->yieldContent(\'foo\'); ?>', $compiler->compileString('@yield(\'foo\')'));
		$this->assertEquals('<?php echo $__env->yieldContent(name(foo)); ?>', $compiler->compileString('@yield(name(foo))'));
	}


	public function testShowsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo $__env->yieldSection(); ?>', $compiler->compileString('@show'));
	}


	public function testLanguageAndChoicesAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php echo \Illuminate\Support\Facades\Lang::get(\'foo\'); ?>', $compiler->compileString("@lang('foo')"));
		$this->assertEquals('<?php echo \Illuminate\Support\Facades\Lang::choice(\'foo\', 1); ?>', $compiler->compileString("@choice('foo', 1)"));
	}


	public function testSectionStartsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php $__env->startSection(\'foo\'); ?>', $compiler->compileString('@section(\'foo\')'));
		$this->assertEquals('<?php $__env->startSection(name(foo)); ?>', $compiler->compileString('@section(name(foo))'));
	}


	public function testStopSectionsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals('<?php $__env->stopSection(); ?>', $compiler->compileString('@stop'));
	}


	public function testCustomExtensionsAreCompiled()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$compiler->extend(function($value) { return str_replace('foo', 'bar', $value); });
		$this->assertEquals('bar', $compiler->compileString('foo'));
	}


	public function testConfiguringContentTags()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$compiler->setContentTags('[[', ']]');
		$compiler->setEscapedContentTags('[[[', ']]]');

		$this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('[[[ $name ]]]'));
		$this->assertEquals('<?php echo $name; ?>', $compiler->compileString('[[ $name ]]'));
		$this->assertEquals('<?php echo $name; ?>', $compiler->compileString('[[
			$name
		]]'));
	}


	protected function getFiles()
	{
		return m::mock('Illuminate\Filesystem\Filesystem');
	}

}
