<?php namespace Illuminate\Contracts\Console;

interface Kernel {

	/**
	 * Handle an incoming console command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return int
	 */
	public function handle($input, $output = null);

}
