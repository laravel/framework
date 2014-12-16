<?php namespace Illuminate\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Command extends \Symfony\Component\Console\Command\Command {

	/**
	 * The Laravel application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $laravel;

	/**
	 * The input interface implementation.
	 *
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * The output interface implementation.
	 *
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Create a new console command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct($this->name);

		// We will go ahead and set the name, description, and parameters on console
		// commands just to make things a little easier on the developer. This is
		// so they don't have to all be manually specified in the constructors.
		$this->setDescription($this->description);

		$this->specifyParameters();
	}

	/**
	 * Specify the arguments and options on the command.
	 *
	 * @return void
	 */
	protected function specifyParameters()
	{
		// We will loop through all of the arguments and options for the command and
		// set them all on the base command instance. This specifies what can get
		// passed into these commands as "parameters" to control the execution.
		foreach ($this->getArguments() as $arguments)
		{
			call_user_func_array(array($this, 'addArgument'), $arguments);
		}

		foreach ($this->getOptions() as $options)
		{
			call_user_func_array(array($this, 'addOption'), $options);
		}
	}

	/**
	 * Run the console command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return integer
	 */
	public function run(InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;

		$this->output = $output;

		return parent::run($input, $output);
	}

	/**
	 * Execute the console command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return mixed
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		return $this->laravel->call([$this, 'fire']);
	}

	/**
	 * Call another console command.
	 *
	 * @param  string  $command
	 * @param  array   $arguments
	 * @return integer
	 */
	public function call($command, array $arguments = array())
	{
		$instance = $this->getApplication()->find($command);

		$arguments['command'] = $command;

		return $instance->run(new ArrayInput($arguments), $this->output);
	}

	/**
	 * Call another console command silently.
	 *
	 * @param  string  $command
	 * @param  array   $arguments
	 * @return integer
	 */
	public function callSilent($command, array $arguments = array())
	{
		$instance = $this->getApplication()->find($command);

		$arguments['command'] = $command;

		return $instance->run(new ArrayInput($arguments), new NullOutput);
	}

	/**
	 * Get the value of a command argument.
	 *
	 * @param  string  $key
	 * @return string|array
	 */
	public function argument($key = null)
	{
		if (is_null($key)) return $this->input->getArguments();

		return $this->input->getArgument($key);
	}

	/**
	 * Get the value of a command option.
	 *
	 * @param  string  $key
	 * @return string|array
	 */
	public function option($key = null)
	{
		if (is_null($key)) return $this->input->getOptions();

		return $this->input->getOption($key);
	}

	/**
	 * Confirm a question with the user.
	 *
	 * @param  string  $question
	 * @param  bool    $default
	 * @return bool
	 */
	public function confirm($question, $default = false)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new ConfirmationQuestion("<question>{$question}</question> ", $default);

		return $helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Prompt the user for input.
	 *
	 * @param  string  $question
	 * @param  string  $default
	 * @return string
	 */
	public function ask($question, $default = null)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new Question("<question>$question</question> ", $default);

		return $helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Prompt the user for input with auto completion.
	 *
	 * @param  string  $question
	 * @param  array   $choices
	 * @param  string  $default
	 * @return string
	 */
	public function askWithCompletion($question, array $choices, $default = null)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new Question("<question>$question</question> ", $default);

		$question->setAutocompleterValues($choices);

		return $helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Prompt the user for input but hide the answer from the console.
	 *
	 * @param  string  $question
	 * @param  bool    $fallback
	 * @return string
	 */
	public function secret($question, $fallback = true)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new Question("<question>$question</question> ");

		$question->setHidden(true)->setHiddenFallback($fallback);

		return $helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Give the user a single choice from an array of answers.
	 *
	 * @param  string  $question
	 * @param  array   $choices
	 * @param  string  $default
	 * @param  mixed   $attempts
	 * @param  bool    $multiple
	 * @return bool
	 */
	public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
	{
		$helper = $this->getHelperSet()->get('question');

		$question = new ChoiceQuestion("<question>$question</question> ", $choices, $default);

		$question->setMaxAttempts($attempts)->setMultiselect($multiple);

		return $helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Format input to textual table
	 *
	 * @param  array   $headers
	 * @param  array   $rows
	 * @param  string  $style
	 * @return void
	 */
	public function table(array $headers, array $rows, $style = 'default')
	{
		$table = new Table($this->output);

		$table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
	}

	/**
	 * Write a string as information output.
	 *
	 * @param  string  $string
	 * @return void
	 */
	public function info($string)
	{
		$this->output->writeln("<info>$string</info>");
	}

	/**
	 * Write a string as standard output.
	 *
	 * @param  string  $string
	 * @return void
	 */
	public function line($string)
	{
		$this->output->writeln($string);
	}

	/**
	 * Write a string as comment output.
	 *
	 * @param  string  $string
	 * @return void
	 */
	public function comment($string)
	{
		$this->output->writeln("<comment>$string</comment>");
	}

	/**
	 * Write a string as question output.
	 *
	 * @param  string  $string
	 * @return void
	 */
	public function question($string)
	{
		$this->output->writeln("<question>$string</question>");
	}

	/**
	 * Write a string as error output.
	 *
	 * @param  string  $string
	 * @return void
	 */
	public function error($string)
	{
		$this->output->writeln("<error>$string</error>");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}

	/**
	 * Get the output implementation.
	 *
	 * @return \Symfony\Component\Console\Output\OutputInterface
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * Get the Laravel application instance.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public function getLaravel()
	{
		return $this->laravel;
	}

	/**
	 * Set the Laravel application instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $laravel
	 * @return void
	 */
	public function setLaravel($laravel)
	{
		$this->laravel = $laravel;
	}

}
