<?php namespace Illuminate\Exception\Console;

use Closure;
use Illuminate\Console\Command;
use React\Socket\Server as SocketServer;
use React\EventLoop\Factory as LoopFactory;

class DebugCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'debug';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Start a live debug console";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$loop = LoopFactory::create();

		// Once we have an event loop instance, we will configure the socket so that it
		// is ready for receiving incoming messages on a given port for the host box
		// that we are currently on. We will then start up the event loop finally.
		$this->configureSocket(new SocketServer($loop));

		$this->info('Live debugger started...'); $loop->run();
	}

	/**
	 * Configure the given socket server.
	 *
	 * @param  \React\Socket\Server  $socket
	 * @param  \Closure  $callback
	 * @return void
	 */
	protected function configureSocket($socket)
	{
		$output = $this->output;

		// Here we will pass the callback that will handle incoming data to the console
		// and we can log it out however we want. We will just write it out using an
		// implementation of a consoles OutputInterface which should perform fine.
		$this->onIncoming($socket, function($data) use ($output)
		{
			$output->write($data);
		});

		$socket->listen(8337, '127.0.0.1');
	}

	/**
	 * Register a callback for incoming data.
	 *
	 * @param  \React\Socket\Server  $socket
	 * @param  \Closure  $callback
	 * @return void
	 */
	protected function onIncoming($socket, Closure $callback)
	{
		$socket->on('connection', function($conn) use ($callback)
		{
			$conn->on('data', $callback);
		});
	}

}