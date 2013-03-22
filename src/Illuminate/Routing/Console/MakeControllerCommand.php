<?php namespace Illuminate\Routing\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Routing\Generators\ControllerGenerator;

class MakeControllerCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'controller:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new resourceful controller';

	/**
	 * The controller generator instance.
	 *
	 * @var Illuminate\Routing\Generators\ControllerGenerator
	 */
	protected $generator;

	/**
	 * The path to the controller directory.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new make controller command instance.
	 *
	 * @param  Illuminate\Routing\Generators\ControllerGenerator  $generator
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(ControllerGenerator $generator, $path)
	{
		parent::__construct();

		$this->path = $path;
		$this->generator = $generator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->generateController();
	}

	/**
	 * Generate a new resourceful controller file.
	 *
	 * @return void
	 */
	protected function generateController()
	{
		$controller = $this->input->getArgument('name');

		// Once we have the controller and resource that we are going to be generating
		// we will grab the path and options. We allow the developers to include or
		// exclude given methods from the resourceful controllers we're building.
		$path = $this->getPath();

		$options = $this->getBuildOptions();

		if ( ! $this->generator->make($controller, $path, $options))
		{
			$this->error('Controller already exists!');
		}
		else
		{
			$this->info('Controller created successfully!');
		}
	}

	/**
	 * Get the path in which to store the controller.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		if ( ! is_null($this->input->getOption('path')))
		{
			return $this->laravel['path.base'].'/'.$this->input->getOption('path');
		}

		return $this->path;
	}

	/**
	 * Get the options for controller generation.
	 *
	 * @return array
	 */
	protected function getBuildOptions()
	{
		$only = $this->explodeOption('only');

		$except = $this->explodeOption('except');

		return compact('only', 'except');
	}

	/**
	 * Get and explode a given input option.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function explodeOption($name)
	{
		$option = $this->input->getOption($name);

		return is_null($option) ? array() : explode(',', $option);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the controller class'),
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
			array('only', null, InputOption::VALUE_OPTIONAL, 'The methods that should be included'),

			array('except', null, InputOption::VALUE_OPTIONAL, 'The methods that should be excluded'),

			array('path', null, InputOption::VALUE_OPTIONAL, 'Where to place the controller'),
		);
	}

}