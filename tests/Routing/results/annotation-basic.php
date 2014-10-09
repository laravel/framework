$router->put('more/{id}', ['uses' => 'App\Http\Controllers\BasicController@doMore', 'domain' => '{id}.account.com', 'as' => NULL, 'middleware' => array (
  0 => 'FooMiddleware',
  1 => 'BarMiddleware',
  2 => 'QuxMiddleware',
), 'where' => array (
  'id' => 'regex',
)]);
$router->group(['middleware' => array (
  0 => 'FooMiddleware',
  1 => 'BarMiddleware',
  2 => 'BoomMiddleware',
  3 => 'BazMiddleware',
), 'prefix' => NULL, 'domain' => '{id}.account.com', 'where' => array (
  'id' => 'regex',
)], function($router) { $router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => array (
  0 => 'index',
), 'names' => array (
  'index' => 'index.name',
)]); });
$router->group(['middleware' => array (
  0 => 'FooMiddleware',
  1 => 'BarMiddleware',
), 'prefix' => NULL, 'domain' => '{id}.account.com', 'where' => array (
  'id' => 'regex',
)], function($router) { $router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => array (
  0 => 'update',
), 'names' => array (
)]); });
