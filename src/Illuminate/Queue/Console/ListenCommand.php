<?php namespace Illuminate\Queue\Console;

use Illuminate\Queue\Listener;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ListenCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:listen';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Listen to a given queue';

	/**
	 * The queue listener instance.
	 *
	 * @var Illuminate\Queue\Listener
	 */
	protected $listener;

	/**
	 * Create a new queue listen command.
	 *
	 * @param  Illuminate\Queue\Listener  $listener
	 * @return void
	 */
	public function __construct(Listener $listener)
	{
		parent::__construct();

		$this->listener = $listener;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$delay = $this->input->getOption('delay');

		// The memory limit is the amount of memory we will allow the script to occupy
		// before killing it and letting a process manager restart it for us, which
		// is to protect us against any memory leaks that will be in the scripts.
		$memory = $this->input->getOption('memory');

		$connection = $this->input->getArgument('connection');

		$timeout = $this->input->getOption('timeout');

		$queue = $this->getQueue($connection);

		$this->listener->listen($connection, $queue, $delay, $memory, $timeout);
	}

	/**
	 * Get the name of the queue connection to listen on.
	 *
	 * @param  string  $connection
	 * @return string
	 */
	protected function getQueue($connection)
	{
		if ($connection === NULL)
		{
			$connection = $this->laravel['config']->get("queue.default");
		}
		
		$queue = $this->laravel['config']->get("queue.connections.{$connection}.queue", 'default');

		return $this->input->getOption('queue') ?: $queue;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('connection', InputArgument::OPTIONAL, 'The name of connection'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', null),

			array('delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0),

			array('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128),

			array('timeout', null, InputOption::VALUE_OPTIONAL, 'Seconds a job may run before timing out', 60),
		);
	}

}
