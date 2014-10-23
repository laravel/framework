$router->put('more/{id}', [
	'uses' => 'App\Http\Controllers\BasicController@doMore',
	'as' => NULL,
	'middleware' => ['FooMiddleware', 'BarMiddleware', 'QuxMiddleware'],
	'where' => ['id' => 'regex'],
	'domain' => '{id}.account.com',
]);

// Resource: foobar/photos@index
$router->group(['middleware' => ['FooMiddleware', 'BarMiddleware', 'BoomMiddleware', 'BazMiddleware'], 'prefix' => NULL, 'where' => ['id' => 'regex'], 'domain' => '{id}.account.com'], function() use ($router)
{
	$router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => ['index'], 'names' => ['index' => 'index.name']]);
});

// Resource: foobar/photos@update
$router->group(['middleware' => ['FooMiddleware', 'BarMiddleware'], 'prefix' => NULL, 'where' => ['id' => 'regex'], 'domain' => '{id}.account.com'], function() use ($router)
{
	$router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => ['update'], 'names' => []]);
});
