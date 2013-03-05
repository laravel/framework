<?php namespace Illuminate\View\Compilers;

use Closure;
use Illuminate\Filesystem\Filesystem;

class BladeCompiler extends Compiler implements CompilerInterface {

	/**
	 * All of the registered extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
		'Extensions',
		'Extends',
		'Comments',
		'Echos',
		'Openings',
		'Closings',
		'Else',
		'Unless',
		'EndUnless',
		'Includes',
		'Each',
		'Yields',
		'Shows',
		'SectionStart',
		'SectionStop',
	);

	/**
	 * Array representing the opening and closing
	 * tags for echoing content.
	 *
	 * @var array
	 */
	protected $contentTags = array('{{', '}}');

	/**
	 * Array representing the opening and closing
	 * tags for echoing raw content.
	 *
	 * @var array
	 */
	protected $escapedContentTags = array('{{{', '}}}');

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path)
	{
		$contents = $this->compileString($this->files->get($path));

		if ( ! is_null($this->cachePath))
		{
			$this->files->put($this->getCompiledPath($path), $contents);
		}
	}

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileString($value)
	{
		foreach ($this->compilers as $compiler)
		{
			$value = $this->{"compile{$compiler}"}($value);
		}

		return $value;
	}

	/**
	 * Register a custom Blade compiler.
	 *
	 * @param  Closure  $compiler
	 * @return void
	 */
	public function extend(Closure $compiler)
	{
		$this->extensions[] = $compiler;
	}

	/**
	 * Execute the user defined extensions.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileExtensions($value)
	{
		foreach ($this->extensions as $compiler)
		{
			$value = call_user_func($compiler, $value);
		}

		return $value;
	}

	/**
	 * Compile Blade template extensions into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileExtends($value)
	{
		// By convention, Blade views using template inheritance must begin with the
		// @extends expression, otherwise they will not be compiled with template
		// inheritance. So, if they do not start with that we will just return.
		if (strpos($value, '@extends') !== 0)
		{
			return $value;
		}

		$lines = preg_split("/(\r?\n)/", $value);

		// Next, we just want to split the values by lines, and create an expression
		// to include the parent layout at the end of the templates. Which allows
		// the sections to get registered before the parent view gets rendered.
		$pattern = $this->createMatcher('extends');

		$replace = '$1@include$2';

		$lines[] = preg_replace($pattern, $replace, $lines[0]);

		// Once we've made the replacements, we'll slice off the first line as it is
		// now just an empty line since the template has been moved to the end of
		// the files. We will let the other sections be registered before this.
		return implode("\r\n", array_slice($lines, 1));
	}

	/**
	 * Compile Blade comments into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileComments($value)
	{
		return preg_replace(sprintf(
			'/%s--((.|\s)*?)--%s/',
			preg_quote($this->contentTags[0]),
			preg_quote($this->contentTags[1])
		), '<?php /* $1 */ ?>', $value);
	}

	/**
	 * Compile Blade echos into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEchos($value)
	{
		$value = preg_replace(sprintf(
			'/%s\s*(.+?)\s*%s/s',
			preg_quote($this->escapedContentTags[0]),
			preg_quote($this->escapedContentTags[1])
		), '<?php echo e($1); ?>', $value);

		return preg_replace(sprintf(
			'/%s\s*(.+?)\s*%s/s',
			preg_quote($this->contentTags[0]),
			preg_quote($this->contentTags[1])
		), '<?php echo $1; ?>', $value);
	}

	/**
	 * Compile Blade structure openings into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileOpenings($value)
	{
		$pattern = '/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/';

		return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
	}

	/**
	 * Compile Blade structure closings into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileClosings($value)
	{
		$pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
	}

	/**
	 * Compile Blade else statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileElse($value)
	{
		$pattern = $this->createPlainMatcher('else');

		return preg_replace($pattern, '$1<?php else: ?>$2', $value);
	}

	/**
	 * Compile Blade unless statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileUnless($value)
	{
		$pattern = $this->createMatcher('unless');

		return preg_replace($pattern, '$1<?php if ( !$2): ?>', $value);
	}

	/**
	 * Compile Blade end unless statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEndUnless($value)
	{
		$pattern = $this->createPlainMatcher('endunless');

		return preg_replace($pattern, '$1<?php endif; ?>$2', $value);
	}

	/**
	 * Compile Blade include statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileIncludes($value)
	{
		$pattern = $this->createOpenMatcher('include');

		$replace = '$1<?php echo $__env->make$2, array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';

		return preg_replace($pattern, $replace, $value);
	}

	/**
	 * Compile Blade each statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEach($value)
	{
		$pattern = $this->createMatcher('each');

		return preg_replace($pattern, '$1<?php echo $__env->renderEach$2; ?>', $value);
	}

	/**
	 * Compile Blade yield statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileYields($value)
	{
		$pattern = $this->createMatcher('yield');

		return preg_replace($pattern, '$1<?php echo $__env->yieldContent$2; ?>', $value);
	}

	/**
	 * Compile Blade show statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileShows($value)
	{
		$pattern = $this->createPlainMatcher('show');

		return preg_replace($pattern, '$1<?php echo $__env->yieldSection(); ?>$2', $value);
	}

	/**
	 * Compile Blade section start statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileSectionStart($value)
	{
		$pattern = $this->createMatcher('section');

		return preg_replace($pattern, '$1<?php $__env->startSection$2; ?>', $value);
	}

	/**
	 * Compile Blade section stop statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileSectionStop($value)
	{
		$pattern = $this->createPlainMatcher('stop');

		return preg_replace($pattern, '$1<?php $__env->stopSection(); ?>$2', $value);
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*\))/';
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createOpenMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*)\)/';
	}

	/**
	 * Create a plain Blade matcher.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createPlainMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*)/';
	}

	/**
	 * Sets the content tags used for the compiler.
	 *
	 * @param  array  $contentTags
	 * @param  array  $raw
	 * @return void
	 */
	public function setContentTags(array $contentTags, $raw = false)
	{
		if (($count = count($contentTags)) !== 2)
		{
			throw new \InvalidArgumentException("Invalid count [$count] of Blade content tags provided.");
		}

		$property = ($raw === true) ? 'escapedContentTags' : 'contentTags';

		$this->{$property} = array_values($contentTags);
	}

	/**
	 * Sets the raw content tags used for the compiler.
	 *
	 * @param  array  $contentTags
	 * @return void
	 */
	public function setEscapedContentTags(array $contentTags)
	{
		$this->setContentTags($contentTags, true);
	}

}
