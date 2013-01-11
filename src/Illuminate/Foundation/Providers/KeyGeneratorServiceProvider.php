<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\KeyGenerateCommand;

class KeyGeneratorServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['command.key.generate'] = $this->app->share(function($app)
		{
			return new KeyGenerateCommand($app['files']);
		});

		$this->commands('command.key.generate');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.key.generate');
	}

}