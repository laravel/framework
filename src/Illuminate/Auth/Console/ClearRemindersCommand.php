<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Auth\Reminders\ReminderRepositoryInterface;

class ClearRemindersCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'auth:clear-reminders';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Flush expired reminders.';

	/**
	 * The password reminder repository.
	 *
	 * @var \Illuminate\Auth\Reminders\ReminderRepositoryInterface  $reminders
	 */
	protected $reminders;

	/**
	 * Create a new reminder clear command instance.
	 *
	 * @param  \Illuminate\Auth\Reminders\ReminderRepositoryInterface  $reminders
	 * @return void
	 */
	public function __construct(ReminderRepositoryInterface $reminders)
	{
		parent::__construct();

		$this->reminders = $reminders;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->reminders->deleteExpired();

		$this->info('Expired reminders cleared!');
	}

}