<?php namespace Illuminate\View\Compilers;

use Closure;

class Blade2Compiler extends Compiler implements CompilerInterface {

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
        $tokens = token_get_all($value);

        $result = '';
        $footer = array();

        foreach($tokens as $token)
        {
            if( is_array($token) )
            {
                list($t_id, $t_content, $t_no) = $token;
                if( $t_id == T_INLINE_HTML ) {
                    $t_content = $this->compileExtensions($t_content);

                    $obj = $this;
                    $t_content = preg_replace_callback(
                        '/\B # we shouldnt have words before
                        @(\w+) # control word should start with @
                        ([ \t]*) # and we can have spaces afterwards
                        (\( ( (?>[^()]+) | (?3) )* \))? # and optional expression within brackets
                        /x',
                        function($match) use($obj, &$footer){
                            return $obj->compileEquation($match[0], $match[1], @$match[3], $match[2], $footer)
                                . (@$match[3]?'':$match[2]);
                        }, $t_content);

                    $t_content = $this->compileComments($t_content);
                    $t_content = $this->compileEchos($t_content);
                }

                $result .= $t_content;
            }
            else
            {
                $result .= $token;
            }
        }

        if( count($footer) )
            $result = ltrim($result, PHP_EOL) . PHP_EOL . implode(PHP_EOL, array_reverse($footer));

		return $result;
	}

    public function compileEquation($content, $func, $expr, $spaces, &$footer)
    {
        switch($func)
        {
            case 'each':
                $replacement = "<?php echo \$__env->renderEach{$expr}; ?>";
                break;
            case 'yield':
                $replacement = "<?php echo \$__env->yieldContent{$expr}; ?>";
                break;
            case 'show':
                $replacement = "<?php echo \$__env->yieldSection(); ?>";
                break;
            case 'section':
                $replacement = "<?php \$__env->startSection{$expr}; ?>";
                break;
            case 'append':
                $replacement = "<?php \$__env->appendSection(); ?>";
                break;
            case 'stop':
                $replacement = "<?php \$__env->stopSection(); ?>";
                break;
            case 'overwrite':
                $replacement = "<?php \$__env->stopSection(true); ?>";
                break;
            case 'if':
            case 'elseif':
            case 'while':
            case 'foreach':
                $replacement = "<?php $func{$spaces}{$expr}: ?>";
                break;
            case 'else':
                $replacement = "<?php else: ?>";
                break;
            case 'endif':
            case 'endforeach':
            case 'endwhile':
                $replacement = "<?php $func; ?>";
                break;
            case 'unless':
                $replacement = "<?php if ( ! $expr): ?>";
                break;
            case 'endunless':
                $replacement = "<?php endif; ?>";
                break;
            case 'lang':
                $replacement = "<?php echo \\Illuminate\\Support\\Facades\\Lang::get$expr; ?>";
                break;
            case 'choice':
                $replacement = "<?php echo \\Illuminate\\Support\\Facades\\Lang::choice$expr; ?>";
                break;
            case 'include':
            case 'extends':
                // remove outer brackets so that (a,b) becomes a,b
                $expr = preg_replace('/^\\((.*)\\)$/', '$1', $expr);
                $data = "<?php echo \$__env->make($expr, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
                if($func == 'include')
                    $replacement = $data;
                else {
                    $footer[] = $data;
                    $replacement = '';
                }
                break;
            default:
                $replacement = $content;
        }

        return $replacement;
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
	 * Compile the "regular" echo statements.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileRegularEchos($value)
	{
		$me = $this;

        // match @{{ content }}
		$pattern = sprintf('/(@)?%s\s*(.+?)\s*%s/s', $this->contentTags[0], $this->contentTags[1]);

		$callback = function($matches) use ($me)
		{
			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$me->compileEchoDefaults($matches[2]).'; ?>';
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
		$me = $this;

        // match {{{ content }}}
		$pattern = sprintf('/%s\s*(.+?)\s*%s/s', $this->escapedTags[0], $this->escapedTags[1]);

		$callback = function($matches) use ($me)
		{
			return '<?php echo e('.$me->compileEchoDefaults($matches[1]).'); ?>';
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

}
