$router->put('more/{id}', ['uses' => 'App\Http\Controllers\BasicController@doMore', 'domain' => '{id}.account.com', 'as' => NULL, 'before' => array (
  0 => 'csrf',
  1 => 'auth',
), 'after' => array (
  0 => 'log',
), 'where' => array (
  'id' => 'regex',
));
$router->group(['before' => array (
  0 => 'auth',
  1 => 'inline',
), 'after' => array (
), 'prefix' => NULL, 'domain' => '{id}.account.com', 'where' => array (
  'id' => 'regex',
)], function($router) { $router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => array (
  0 => 'index',
), 'names' => array (
  'index' => 'index.name',
)]); });
$router->group(['before' => array (
  0 => 'csrf',
  1 => 'auth',
  2 => 'inline',
), 'after' => array (
), 'prefix' => NULL, 'domain' => '{id}.account.com', 'where' => array (
  'id' => 'regex',
)], function($router) { $router->resource('foobar/photos', 'App\\Http\\Controllers\\BasicController', ['only' => array (
  0 => 'update',
), 'names' => array (
)]); });