<?php namespace Illuminate\View\Compilers;

use Closure;

class BladeCompiler extends Compiler implements CompilerInterface {

	/**
	 * All of the registered extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
		'Extensions',
		'Statements',
		'Comments',
		'Echos'
	);

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $contentTags = array('{{', '}}');

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $escapedTags = array('{{{', '}}}');

	/**
	 * Array of footer lines to be added to template.
	 *
	 * @var array
	 */
	protected $footer = array();

	/**
	 * Counter to keep track of nested forelse statements.
	 *
	 * @var int
	 */
	protected $forelseCounter = 0;

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path = null)
	{
		$this->footer = array();

		if ($path)
		{
			$this->setPath($path);
		}

		$contents = $this->compileString($this->files->get($path));

		if ( ! is_null($this->cachePath))
		{
			$this->files->put($this->getCompiledPath($this->getPath()), $contents);
		}
	}

	/**
	 * Get the path currently being compiled.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set the path currently being compiled.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileString($value)
	{
		$result = '';

		// Here we will loop through all of the tokens returned by the Zend lexer and
		// parse each one into the corresponding valid PHP. We will then have this
		// template as the correctly rendered PHP that can be rendered natively.
		foreach (token_get_all($value) as $token)
		{
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		// If there are any footer lines that need to get added to a template we will
		// add them here at the end of the template. This gets used mainly for the
		// template inheritance via the extends keyword that should be appended.
		if (count($this->footer) > 0)
		{
			$result = ltrim($result, PHP_EOL)
					.PHP_EOL.implode(PHP_EOL, array_reverse($this->footer));
		}

		return $result;
	}

	/**
	 * Parse the tokens from the template.
	 *
	 * @param  array  $token
	 * @return string
	 */
	protected function parseToken($token)
	{
		list($id, $content) = $token;

		if ($id == T_INLINE_HTML)
		{
			foreach ($this->compilers as $type)
			{
				$content = $this->{"compile{$type}"}($content);
			}
		}

		return $content;
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
			$value = call_user_func($compiler, $value, $this);
		}

		return $value;
	}

	/**
	 * Compile Blade comments into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileComments($value)
	{
		$pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->contentTags[0], $this->contentTags[1]);

		return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
	}

	/**
	 * Compile Blade echos into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEchos($value)
	{
		$difference = strlen($this->contentTags[0]) - strlen($this->escapedTags[0]);

		if ($difference > 0)
		{
			return $this->compileEscapedEchos($this->compileRegularEchos($value));
		}

		return $this->compileRegularEchos($this->compileEscapedEchos($value));
	}

	/**
	 * Compile Blade Statements that start with "@"
	 *
	 * @param  string  $value
	 * @return mixed
	 */
	protected function compileStatements($value)
	{
		$callback = function($match)
		{
			if (method_exists($this, $method = 'compile'.ucfirst($match[1])))
			{
				$match[0] = $this->$method(array_get($match, 3));
			}

			return isset($match[3]) ? $match[0] : $match[0].$match[2];
		};

		return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $value);
	}

	/**
	 * Compile the "regular" echo statements.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileRegularEchos($value)
	{
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

		$callback = function($matches)
		{
			$whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$this->compileEchoDefaults($matches[2]).'; ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the escaped echo statements.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEscapedEchos($value)
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

		$callback = function($matches)
		{
			$whitespace = empty($matches[2]) ? '' : $matches[2].$matches[2];

			return '<?php echo e('.$this->compileEchoDefaults($matches[1]).'); ?>'.$whitespace;
		};

		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
	 * Compile the default values for the echo statement.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileEchoDefaults($value)
	{
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
	}

	/**
	 * Compile the each statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEach($expression)
	{
		return "<?php echo \$__env->renderEach{$expression}; ?>";
	}

	/**
	 * Compile the yield statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileYield($expression)
	{
		return "<?php echo \$__env->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the show statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileShow($expression)
	{
		return "<?php echo \$__env->yieldSection(); ?>";
	}

	/**
	 * Compile the section statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileSection($expression)
	{
		return "<?php \$__env->startSection{$expression}; ?>";
	}

	/**
	 * Compile the append statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileAppend($expression)
	{
		return "<?php \$__env->appendSection(); ?>";
	}

	/**
	 * Compile the end-section statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndsection($expression)
	{
		return "<?php \$__env->stopSection(); ?>";
	}

	/**
	 * Compile the stop statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileStop($expression)
	{
		return "<?php \$__env->stopSection(); ?>";
	}

	/**
	 * Compile the overwrite statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileOverwrite($expression)
	{
		return "<?php \$__env->stopSection(true); ?>";
	}

	/**
	 * Compile the unless statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileUnless($expression)
	{
		return "<?php if ( ! $expression): ?>";
	}

	/**
	 * Compile the end unless statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndunless($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the lang statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileLang($expression)
	{
		return "<?php echo \\Illuminate\\Support\\Facades\\Lang::get$expression; ?>";
	}

	/**
	 * Compile the choice statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileChoice($expression)
	{
		return "<?php echo \\Illuminate\\Support\\Facades\\Lang::choice$expression; ?>";
	}

	/**
	 * Compile the else statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileElse($expression)
	{
		return "<?php else: ?>";
	}

	/**
	 * Compile the for statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileFor($expression)
	{
		return "<?php for{$expression}: ?>";
	}

	/**
	 * Compile the foreach statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileForeach($expression)
	{
		return "<?php foreach{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileForelse($expression)
	{
		$empty = '$__empty_' . ++$this->forelseCounter;

		return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
	}

	/**
	 * Compile the if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileIf($expression)
	{
		return "<?php if{$expression}: ?>";
	}

	/**
	 * Compile the else-if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileElseif($expression)
	{
		return "<?php elseif{$expression}: ?>";
	}

	/**
	 * Compile the forelse statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEmpty($expression)
	{
		$empty = '$__empty_' . $this->forelseCounter--;

		return "<?php endforeach; if ({$empty}): ?>";
	}

	/**
	 * Compile the while statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileWhile($expression)
	{
		return "<?php while{$expression}: ?>";
	}

	/**
	 * Compile the end-while statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndwhile($expression)
	{
		return "<?php endwhile; ?>";
	}

	/**
	 * Compile the end-for statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndfor($expression)
	{
		return "<?php endfor; ?>";
	}

	/**
	 * Compile the end-for-each statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndforeach($expression)
	{
		return "<?php endforeach; ?>";
	}

	/**
	 * Compile the end-if statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndif($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the end-for-else statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndforelse($expression)
	{
		return "<?php endif; ?>";
	}

	/**
	 * Compile the extends statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileExtends($expression)
	{
		if (starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		$data = "<?php echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";

		$this->footer[] = $data;

		return '';
	}

	/**
	 * Compile the include statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileInclude($expression)
	{
		if (starts_with($expression, '('))
		{
			$expression = substr($expression, 1, -1);
		}

		return "<?php echo \$__env->make($expression, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
	}

	/**
	 * Compile the stack statements into the content
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileStack($expression)
	{
		return "<?php echo \$__env->yieldContent{$expression}; ?>";
	}

	/**
	 * Compile the push statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compilePush($expression)
	{
		return "<?php \$__env->startSection{$expression}; ?>";
	}

	/**
	 * Compile the endpush statements into valid PHP.
	 *
	 * @param  string  $expression
	 * @return string
	 */
	protected function compileEndpush($expression)
	{
		return "<?php \$__env->appendSection(); ?>";
	}

	/**
	 * Register a custom Blade compiler.
	 *
	 * @param  \Closure  $compiler
	 * @return void
	 */
	public function extend(Closure $compiler)
	{
		$this->extensions[] = $compiler;
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
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @param  bool    $escaped
	 * @return void
	 */
	public function setContentTags($openTag, $closeTag, $escaped = false)
	{
		$property = ($escaped === true) ? 'escapedTags' : 'contentTags';

		$this->{$property} = array(preg_quote($openTag), preg_quote($closeTag));
	}

	/**
	 * Sets the escaped content tags used for the compiler.
	 *
	 * @param  string  $openTag
	 * @param  string  $closeTag
	 * @return void
	 */
	public function setEscapedContentTags($openTag, $closeTag)
	{
		$this->setContentTags($openTag, $closeTag, true);
	}

	/**
	* Gets the content tags used for the compiler.
	*
	* @return string
	*/
	public function getContentTags()
	{
		return $this->contentTags;
	}

	/**
	* Gets the escaped content tags used for the compiler.
	*
	* @return string
	*/
	public function getEscapedContentTags()
	{
		return $this->escapedTags;
	}

}
