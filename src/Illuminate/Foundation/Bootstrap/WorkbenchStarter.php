<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Workbench\Starter as Workbench;
use Illuminate\Contracts\Foundation\Application;

class WorkbenchStarter {

	public function bootstrap(Application $app)
	{
		if(is_dir($workbench = base_path('workbench')))
		{
			Workbench::start($workbench);
		}
	}
} 