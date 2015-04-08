<?php namespace Illuminate\Console;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Application as ConsoleApplication;

class LaravelStyle extends SymfonyStyle {

	/**
	 * The maximum line length to display.
	 *
	 * @var int
	 */
	private $lineLength;

	/**
	 * Create a new Laravel style instance.
	 *
	 * @param  InputInterface  $input
	 * @param  OutputInterface  $output
	 * @return void
	 */
	public function __construct(InputInterface $input, OutputInterface $output)
	{
		$this->lineLength = min($this->getTerminalWidth(), self::MAX_LINE_LENGTH);

		parent::__construct($input, $output);
	}

	/**
	 * Formats informational text.
	 *
	 * @param  string|array  $messages
	 * @return void
	 */
	public function text($messages)
	{
		foreach ((array) $messages as $message)
		{
			$this->writeln($message);
		}
	}

	/**
	 * Formats a success result bar.
	 *
	 * @param  string|array  $message
	 * @return void
	 */
	public function success($message)
	{
		$this->styledText($message, 'info');
	}

	/**
	 * Formats a note.
	 *
	 * @param  string|array  $message
	 * @return void
	 */
	public function note($message)
	{
		$this->styledText($message, 'comment');
	}

	/**
	 * Formats an error message.
	 *
	 * @param  string|array  $message
	 * @return void
	 */
	public function error($message)
	{
		$this->styledText($message, 'error');
	}

	/**
	 * Formats a warning block.
	 *
	 * @param  string|array  $message
	 * @return void
	 */
	public function warning($message)
	{
		$this->borderedBlock($message, 'comment');
	}

	/**
	 * Formats a caution block.
	 *
	 * @param  string|array  $message
	 * @return void
	 */
	public function caution($message)
	{
		$this->borderedBlock($message, 'error', '!');
	}

    /**
     * Formats a table.
     *
     * @param  array $headers
     * @param  array $rows
     * @return void
     */
	public function table(array $headers, array $rows)
	{
		$table = new Table($this);

		$table->setHeaders($headers)->setRows($rows)->setStyle('default')->render();
	}

	/**
	 * Formats text with a given style.
	 *
	 * @param  string|array  $messages
	 * @param  string|null  $style
	 * @return void
	 */
	protected function styledText($messages, $style = null)
	{
		foreach ((array) $messages as $message)
		{
			if ($style)
			{
				$message = sprintf('<%s>%s</>', $style, $message);
			}

			$this->writeln($message);
		}
	}

	/**
	 * Formats a bordered block with a given style.
	 *
	 * @param  string|array  $messages
	 * @param  string|null  $style
	 * @param  string  $borderChar
	 * @param  int  $paddingSize
	 * @return void
	 */
	protected function borderedBlock($messages, $style = null, $borderChar = '*', $paddingSize = 3)
	{
		$lines = [];
		$messages = (array) $messages;
		$prefix = $borderChar . str_repeat(' ', $paddingSize);

		// Get the length of the longest string
		$length = max(array_map('Symfony\Component\Console\Helper\Helper::strlen',  $messages));
		$length = min($length, $this->lineLength - 2 * $paddingSize - 2);

		foreach ($messages as $key => $message)
		{
			// Explode the message if it's too long.
			$message = OutputFormatter::escape($message);
			$parts = explode("\n", wordwrap($message, $length));

			// Add new line after each message, if not only 1 message
			if (count($messages) > 1 && $key < count($messages) - 1) {
				$parts[] = '';
			}

			// Add the parts with a prefix/suffix
			foreach ($parts as $part)
			{
				$suffix = str_repeat(' ', $paddingSize + $length - Helper::strlen($part)) . $borderChar;
				$lines[] = $prefix . $part . $suffix;
			}
		}

		// Add border as first and last item in the array
		$border = str_repeat($borderChar, $length + 2 * $paddingSize + 2);
		$lines[] = $border;
		array_unshift($lines, $border);

		// Write all the messages in the correct style
		$this->styledText($lines, $style);
	}

	/**
	 * Get the width of the terminal window.
	 *
	 * @return int
	 */
	private function getTerminalWidth()
	{
		$application = new ConsoleApplication();
		$dimensions = $application->getTerminalDimensions();

		return $dimensions[0] ?: self::MAX_LINE_LENGTH;
	}
}
