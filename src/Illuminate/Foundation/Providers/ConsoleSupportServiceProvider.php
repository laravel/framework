<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * The provider class names.
	 *
	 * @var array
	 */
	protected $providers = [
		'Illuminate\Auth\GeneratorServiceProvider',
		'Illuminate\Database\MigrationServiceProvider',
		'Illuminate\Database\SeedServiceProvider',
		'Illuminate\Foundation\Providers\ComposerServiceProvider',
		'Illuminate\Foundation\Providers\PublisherServiceProvider',
		'Illuminate\Queue\FailConsoleServiceProvider',
		'Illuminate\Routing\GeneratorServiceProvider',
		'Illuminate\Session\CommandsServiceProvider',
		'Illuminate\Workbench\WorkbenchServiceProvider',
	];

}
