<?php namespace Illuminate\Filesystem;

use Symfony\Component\Finder\Finder;

class ClassFinder {

	/**
	 * Find all the class and interface names in a given directory.
	 *
	 * @param  string  $directory
	 * @return array
	 */
	public function findClasses($directory)
	{
		$classes = [];

		foreach (Finder::create()->in($directory)->name('*.php') as $file)
		{
			$classes[] = $this->findClass($file->getRealPath());
		}

		return array_filter($classes);
	}

	/**
	 * Extract the class name from the file at the given path.
	 *
	 * @param  string  $path
	 * @return string|null
	 */
	public function findClass($path)
	{
		$namespace = null;

		$tokens = token_get_all(file_get_contents($path));

		foreach ($tokens as $key => $token)
		{
			if ($this->tokenIsNamespace($token))
			{
				$namespace = $this->getNamespace($key + 2, $tokens);
			}
			elseif ($this->tokenIsClassOrInterface($token))
			{
				return ltrim($namespace.'\\'.$this->getClass($key + 2, $tokens), '\\');
			}
		}
	}

	/**
	 * Find the namespace in the tokens starting at a given key.
	 *
	 * @param  int  $key
	 * @param  array  $tokens
	 * @return string
	 */
	protected function getNamespace($key, array $tokens)
	{
		$namespace = null;

		$tokenCount = count($tokens);

		for ($i = $key; $i < $tokenCount; $i++)
		{
			if ($this->isPartOfNamespace($tokens[$i]))
			{
				$namespace .= $tokens[$i][1];
			}
			elseif ($tokens[$i] == ';')
			{
				return $namespace;
			}
		}
	}

	/**
	 * Find the class in the tokens starting at a given key.
	 *
	 * @param  int  $key
	 * @param  array  $tokens
	 * @return string
	 */
	protected function getClass($key, array $tokens)
	{
		$class = null;

		$tokenCount = count($tokens);

		for ($i = $key; $i < $tokenCount; $i++)
		{
			if ($this->isPartOfClass($tokens[$i]))
			{
				$class .= $tokens[$i][1];
			}
			elseif ($this->isWhitespace($tokens[$i]))
			{
				return $class;
			}
		}
	}

	/**
	 * Determine if the given token is a namespace keyword.
	 *
	 * @param  array|string  $token
	 * @return bool
	 */
	protected function tokenIsNamespace($token)
	{
		return is_array($token) && $token[0] == T_NAMESPACE;
	}

	/**
	 * Determine if the given token is a class or interface keyword.
	 *
	 * @param  array|string  $token
	 * @return bool
	 */
	protected function tokenIsClassOrInterface($token)
	{
		return is_array($token) && ($token[0] == T_CLASS || $token[0] == T_INTERFACE);
	}

	/**
	 * Determine if the given token is part of the namespace.
	 *
	 * @param  array|string  $token
	 * @return bool
	 */
	protected function isPartOfNamespace($token)
	{
		return is_array($token) && ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR);
	}

	/**
	 * Determine if the given token is part of the class.
	 *
	 * @param  array|string  $token
	 * @return bool
	 */
	protected function isPartOfClass($token)
	{
		return is_array($token) && $token[0] == T_STRING;
	}

	/**
	 * Determine if the given token is whitespace.
	 *
	 * @param  array|string  $token
	 * @return bool
	 */
	protected function isWhitespace($token)
	{
		return is_array($token) && $token[0] == T_WHITESPACE;
	}

}
