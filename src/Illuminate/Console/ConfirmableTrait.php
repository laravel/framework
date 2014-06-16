<?php namespace Illuminate\Console;

trait ConfirmableTrait {

	/**
	 * Confirm before proceeding with the action
	 *
	 * @return bool
	 */
	public function confirmToProceed()
	{
		if ($this->getLaravel()->environment() == 'production')
		{
			if ($this->option('force')) return true;

			$this->comment('**************************************');
			$this->comment('*     Application In Production!     *');
			$this->comment('**************************************');
			$this->output->writeln('');

			$confirmed = $this->confirm('Do you really wish to run this command?');

			if ( ! $confirmed)
			{
			    $this->comment('Command Cancelled!');

			    return false;
			}
		}

		return true;
	}

}
