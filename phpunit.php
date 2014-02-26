<?php

error_reporting(E_ALL);

set_error_handler(function($no, $str, $file, $line, $context) {
	// allow user-suppressed errors to pass
	if (error_reporting() === 0) return;
	echo "PHP error no. $no - $str" . PHP_EOL . "In $file on line $line" . PHP_EOL;
	exit(1);
});

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require __DIR__.'/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Set The Default Timezone
|--------------------------------------------------------------------------
|
| Here we will set the default timezone for PHP. PHP is notoriously mean
| if the timezone is not explicitly set. This will be used by each of
| the PHP date and date-time functions throughout the application.
|
*/

date_default_timezone_set('UTC');

Carbon\Carbon::setTestNow(Carbon\Carbon::now());
