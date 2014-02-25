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
	 * Array of opening and closing tags for echos.
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
	 * Array of footer lines to be added to template
	 *
	 * @var array
	 */
	protected $footer;

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path)
	{
		$this->footer = array();
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
		$tokens = token_get_all($value);

		$result = '';

		foreach ($tokens as $token)
		{
			$result .= (is_array($token) ? $this->parseToken($token) : $token);
		}

		if (count($this->footer))
		{
			$result = ltrim($result, PHP_EOL) . PHP_EOL . implode(PHP_EOL, array_reverse($this->footer));
		}

		return $result;
	}

	/**
	 * Parse the tokens.
	 *
	 * @param  array  $token
	 * @return string
	 */
	protected function parseToken($token)
	{
		list($id, $content, $no) = $token;

		if ($id == T_INLINE_HTML)
		{
			$content = $this->compileExtensions($content);

			$content = $this->compileStatements($content);

			$content = $this->compileComments($content);

			$content = $this->compileEchos($content);
		}

		return $content;
	}

	protected function compileEach($expr)
	{
		return "<?php echo \$__env->renderEach{$expr}; ?>";
	}

	protected function compileYield($expr)
	{
		return "<?php echo \$__env->yieldContent{$expr}; ?>";
	}

	protected function compileShow($expr)
	{
		return "<?php echo \$__env->yieldSection(); ?>";
	}

	protected function compileSection($expr)
	{
		return "<?php \$__env->startSection{$expr}; ?>";
	}

	protected function compileAppend($expr)
	{
		return "<?php \$__env->appendSection(); ?>";
	}

	protected function compileStop($expr)
	{
		return "<?php \$__env->stopSection(); ?>";
	}

	protected function compileOverwrite($expr)
	{
		return "<?php \$__env->stopSection(true); ?>";
	}

	protected function compileUnless($expr)
	{
		return "<?php if ( ! $expr): ?>";
	}

	protected function compileEndunless($expr)
	{
		return "<?php endif; ?>";
	}

	protected function compileLang($expr)
	{
		return "<?php echo \\Illuminate\\Support\\Facades\\Lang::get$expr; ?>";
	}

	protected function compileChoice($expr)
	{
		return "<?php echo \\Illuminate\\Support\\Facades\\Lang::choice$expr; ?>";
	}

	protected function compileElse($expr)
	{
		return "<?php else: ?>";
	}

	protected function compileForeach($expr)
	{
		return "<?php foreach{$expr}: ?>";
	}

	protected function compileIf($expr)
	{
		return "<?php if{$expr}: ?>";
	}

	protected function compileElseif($expr)
	{
		return "<?php elseif{$expr}: ?>";
	}

	protected function compileWhile($expr)
	{
		return "<?php while{$expr}: ?>";
	}

	protected function compileEndwhile($expr)
	{
		return "<?php endwhile; ?>";
	}

	protected function compileEndforeach($expr)
	{
		return "<?php endforeach; ?>";
	}

	protected function compileEndif($expr)
	{
		return "<?php endif; ?>";
	}

	protected function compileExtends($expr)
	{
		// remove outer brackets so that (a,b) becomes a,b
		if (starts_with($expr, '('))
		{
			$expr = substr($expr, 1, -1);
		}

		$data = "<?php echo \$__env->make($expr, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";

		$this->footer[] = $data;

		return '';
	}

	protected function compileInclude($expr)
	{
		// remove outer brackets so that (a,b) becomes a,b
		if (starts_with($expr, '('))
		{
			$expr = substr($expr, 1, -1);
		}

		return "<?php echo \$__env->make($expr, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
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
		// match {{--comment--}}
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
	 * @param string $value
	 *
	 * @return mixed
	 */
	protected function compileStatements($value)
	{
		$callback = function($match)
		{
			if (method_exists($this, $method = 'compile' . ucfirst($match[1])))
			{
				$match[0] = $this->$method(@$match[3]);
			}

			return $match[0] . (@$match[3]?'':$match[2]);
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
		// match @{{ content }}
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s/s', $this->contentTags[0], $this->contentTags[1]);

		$callback = function($matches)
		{
			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$this->compileEchoDefaults($matches[2]).'; ?>';
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
		// match {{{ content }}}
		$pattern = sprintf('/%s\s*(.+?)\s*%s/s', $this->escapedTags[0], $this->escapedTags[1]);

		$callback = function($matches)
		{
			return '<?php echo e('.$this->compileEchoDefaults($matches[1]).'); ?>';
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
		// match {{ $a or $b }}
		return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
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
