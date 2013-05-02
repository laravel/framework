## Illuminate Database

### Usage Outside Of Laravel 4

```
$config = array(
	'fetch' => PDO::FETCH_CLASS,
	'default' => 'mysql',
	'connections' => array(
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'laravel',
			'username'  => 'root',
			'password'  => 'password',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);

$capsule = new Illuminate\Database\Capsule($config);

// If you want to use the Eloquent ORM...
$capsule->bootEloquent();

// Making A Query Builder Call...
$capsule->connection()->table('users')->where('id', 1)->first();

// Making A Schema Builder Call...
$capsule->connection()->schema()->create('users', function($t)
{
	$t->increments('id');
	$t->string('email');
	$t->timestamps();
});
```