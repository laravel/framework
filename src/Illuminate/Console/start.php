<?php

/*
|--------------------------------------------------------------------------
| Create The Artisan Application
|--------------------------------------------------------------------------
|
| Now we're ready to create the Artisan console application, which will
| be responsible for running the appropriate command. The console is
| built on top of the robust, powerful Symfony console components.
|
*/

use Illuminate\Console\Application;

$artisan = new Application('Laravel Framework', $app::VERSION);

$app->instance('artisan', $artisan);

/*
|--------------------------------------------------------------------------
| Set The Laravel Exception Handler
|--------------------------------------------------------------------------
|
| We'll go ahead and set the Laravel exception handler so the console can
| call these handler when an exception is thrown from the CLI. This is
| important since there could be loggers, etc. setup for exceptions.
|
*/

$artisan->setExceptionHandler($app['exception']);

/*
|--------------------------------------------------------------------------
| Set The Laravel Application
|--------------------------------------------------------------------------
|
| When creating the Artisan application, we will set the Laravel app on
| the console so that we can easily access it from our commands when
| necessary, which allows us to quickly access other app services.
|
*/

$artisan->setLaravel($app);

/*
|--------------------------------------------------------------------------
| Register The Artisan Commands
|--------------------------------------------------------------------------
|
| Each available Artisan command must be registered with the console so
| that it is available to be called. We'll register every command so
| the console gets access to each of the command object instances.
|
*/

require $app['path'].'/start/artisan.php';

return $artisan;