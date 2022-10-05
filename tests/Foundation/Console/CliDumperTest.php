<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\CliDumper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class CliDumperTest extends TestCase
{
    use VarDumperTestTrait;

    protected function setUp(): void
    {
        CliDumper::resolveDumpSourceUsing(function () {
            return [
                '/my-work-director/app/routes/console.php',
                'app/routes/console.php',
                18,
            ];
        });
    }

    public function testString()
    {
        $output = $this->dump('string');

        $expected = "\"string\" // app/routes/console.php:18\n";

        $this->assertSame($expected, $output);
    }

    public function testInteger()
    {
        $output = $this->dump(1);

        $expected = "1 // app/routes/console.php:18\n";

        $this->assertSame($expected, $output);
    }

    public function testFloat()
    {
        $output = $this->dump(1.1);

        $expected = "1.1 // app/routes/console.php:18\n";

        $this->assertSame($expected, $output);
    }

    public function testArray()
    {
        $output = $this->dump(['string', 1, 1.1, ['string', 1, 1.1]]);

        $expected = <<<'EOF'
        array:4 [ // app/routes/console.php:18
          0 => "string"
          1 => 1
          2 => 1.1
          3 => array:3 [
            0 => "string"
            1 => 1
            2 => 1.1
          ]
        ]

        EOF;

        $this->assertSame($expected, $output);
    }

    public function testBoolean()
    {
        $output = $this->dump(true);

        $expected = "true // app/routes/console.php:18\n";

        $this->assertSame($expected, $output);
    }

    public function testObject()
    {
        $user = new stdClass();
        $user->name = 'Guus';

        $output = $this->dump($user);

        $objectId = spl_object_id($user);

        $expected = <<<EOF
        {#$objectId // app/routes/console.php:18
          +"name": "Guus"
        }

        EOF;

        $this->assertSame($expected, $output);
    }

    public function testNull()
    {
        $output = $this->dump(null);

        $expected = "null // app/routes/console.php:18\n";

        $this->assertSame($expected, $output);
    }

    public function testContainer()
    {
        $container = new Container();

        $container->bind(static::class, fn () => $this);
        $container->alias(static::class, 'bar');
        $container->extend('bar', fn () => $this);
        $container->make('bar');

        $fqcn = static::class;
        $expected = <<<EOD
		Illuminate\Container\Container { // app/routes/console.php:18
		  #bindings: array:1 [
		    "{$fqcn}" => array:2 [
		      "concrete" => Closure() {
		        class: "{$fqcn}"
		        this: {$fqcn} {#1 …}
		      }
		      "shared" => false
		    ]
		  ]
		  #aliases: array:1 [
		    "bar" => "{$fqcn}"
		  ]
		  #resolved: array:1 [
		    "{$fqcn}" => true
		  ]
		  #extenders: array:1 [
		    "{$fqcn}" => array:1 [
		      0 => Closure() {
		        class: "{$fqcn}"
		        this: {$fqcn} {#1 …}
		      }
		    ]
		  ]
		   …%d
		}
		EOD;

        $this->assertDumpMatchesFormat($expected, $container);
    }

    public function testCarbonDate()
    {
        $carbon = Carbon::parse('2022-01-18 19:44:02.572622', 'America/New_York');

        $expected = <<<EOD
		Illuminate\Support\Carbon @%d { // app/routes/console.php:18
		  date: "2022-01-18 19:44:02.572622 America/New_York (-05:00)"
		  #endOfTime: false
		  #startOfTime: false
		   …%d
		}
		EOD;

        $this->assertDumpMatchesFormat($expected, $carbon);
    }

    public function testRequest()
    {
        $request = Request::create('/1');

        $expected = <<<EOD
		Illuminate\Http\Request { // app/routes/console.php:18
		  +attributes: Symfony\Component\HttpFoundation\ParameterBag {}
		  +request: Symfony\Component\HttpFoundation\InputBag {}
		  +query: Symfony\Component\HttpFoundation\InputBag {}
		  +server: Symfony\Component\HttpFoundation\ServerBag {
		    SERVER_NAME: "localhost"
		    SERVER_PORT: 80
		    HTTP_HOST: "localhost"
		    HTTP_USER_AGENT: "Symfony"
		    HTTP_ACCEPT: "%s"
		    HTTP_ACCEPT_LANGUAGE: "%s"
		    HTTP_ACCEPT_CHARSET: "%s"
		    REMOTE_ADDR: "%s"
		    SCRIPT_NAME: ""
		    SCRIPT_FILENAME: ""
		    SERVER_PROTOCOL: "HTTP/1.1"
		    REQUEST_TIME: %d
		    REQUEST_TIME_FLOAT: %d.%d
		    PATH_INFO: ""
		    REQUEST_METHOD: "GET"
		    REQUEST_URI: "/1"
		    QUERY_STRING: ""
		  }
		  +files: Symfony\Component\HttpFoundation\FileBag {}
		  +cookies: Symfony\Component\HttpFoundation\InputBag {}
		  +headers: Symfony\Component\HttpFoundation\HeaderBag {
		    host: "localhost"
		    user-agent: "Symfony"
		    accept: "%s"
		    accept-language: "%s"
		    accept-charset: "%s"
		    #cacheControl: []
		  }
		  #defaultLocale: "en"
		  -isHostValid: true
		  -isForwardedValid: true
		  pathInfo: "/1"
		  requestUri: "/1"
		  baseUrl: ""
		  basePath: ""
		  method: "GET"
		  format: "html"
		   …%d
		}
		EOD;

        $this->assertDumpMatchesFormat($expected, $request);
    }

    public function testResponse()
    {
        $response = new Response('Hello world.');

        $expected = <<<EOD
		Illuminate\Http\Response { // app/routes/console.php:18
		  +headers: Symfony\Component\HttpFoundation\ResponseHeaderBag {
		    cache-control: "%s"
		    date: "%s"
		    #cacheControl: []
		  }
		  #content: "Hello world."
		  #version: "%d.%d"
		  #statusCode: 200
		  #statusText: "OK"
		  +original: "Hello world."
		   …%d
		}
		EOD;

        $this->assertDumpMatchesFormat($expected, $response);
    }

    public function testModel()
    {
        $model = new CliDumperModel();

        $expected = <<<EOD
        Illuminate\Tests\Foundation\Console\CliDumperModel { // app/routes/console.php:18
          #attributes: []
          +exists: false
          +wasRecentlyCreated: false
          #relations: []
          #connection: null
          #table: null
          #original: []
          #changes: []
           …%d
        }

        EOD;

        $this->assertDumpMatchesFormat($expected, $model);
    }

    public function testWhenIsFileViewIsNotViewCompiled()
    {
        $file = '/my-work-directory/routes/console.php';

        $output = new BufferedOutput();
        $dumper = new CliDumper(
            $output,
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('isCompiledViewFile');
        $method->setAccessible(true);
        $isCompiledViewFile = $method->invoke($dumper, $file);

        $this->assertFalse($isCompiledViewFile);
    }

    public function testWhenIsFileViewIsViewCompiled()
    {
        $file = '/my-work-directory/storage/framework/views/6687c33c38b71a8560.php';

        $output = new BufferedOutput();
        $dumper = new CliDumper(
            $output,
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('isCompiledViewFile');
        $method->setAccessible(true);
        $isCompiledViewFile = $method->invoke($dumper, $file);

        $this->assertTrue($isCompiledViewFile);
    }

    public function testGetOriginalViewCompiledFile()
    {
        $compiled = __DIR__.'/../fixtures/fake-compiled-view.php';
        $original = '/my-work-directory/resources/views/welcome.blade.php';

        $output = new BufferedOutput();
        $dumper = new CliDumper(
            $output,
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('getOriginalFileForCompiledView');
        $method->setAccessible(true);

        $this->assertSame($original, $method->invoke($dumper, $compiled));
    }

    public function testWhenGetOriginalViewCompiledFileFails()
    {
        $compiled = __DIR__.'/../fixtures/fake-compiled-view-without-source-map.php';
        $original = $compiled;

        $output = new BufferedOutput();
        $dumper = new CliDumper(
            $output,
            '/my-work-directory',
            '/my-work-directory/storage/framework/views'
        );

        $reflection = new ReflectionClass($dumper);
        $method = $reflection->getMethod('getOriginalFileForCompiledView');
        $method->setAccessible(true);

        $this->assertSame($original, $method->invoke($dumper, $compiled));
    }

    public function testUnresolvableSource()
    {
        CliDumper::resolveDumpSourceUsing(fn () => null);

        $output = $this->dump('string');

        $expected = "\"string\"\n";

        $this->assertSame($expected, $output);
    }

    public function testUnresolvableLine()
    {
        CliDumper::resolveDumpSourceUsing(function () {
            return [
                '/my-work-directory/resources/views/welcome.blade.php',
                'resources/views/welcome.blade.php',
                null,
            ];
        });

        $output = $this->dump('hey from view');

        $expected = "\"hey from view\" // resources/views/welcome.blade.php\n";

        $this->assertSame($expected, $output);
    }

    protected function dump($value)
    {
        $output = new BufferedOutput();
        $dumper = new CliDumper(
            $output,
            '/my-work-directory',
            '/my-work-directory/storage/framework/views',
        );

        $dumper->handle($value);

        return $output->fetch();
    }

    protected function getDump($data, $key = null, int $filter = 0): ?string
    {
        $dumper = new CliDumper(
            $output = new BufferedOutput(),
            '/my-work-directory',
            '/my-work-directory/storage/framework/views',
        );

        $cloner = $dumper->getDefaultCloner();
        $cloner->setMaxItems(-1);

        $data = $cloner->cloneVar($data, $filter)->withRefHandles(false);

        if (null !== $key) {
            $data = $data->seek($key);
            if (null === $data) {
                return null;
            }
        }

        $dumper->dumpWithSource($data);

        return rtrim($output->fetch());
    }

    protected function tearDown(): void
    {
        CliDumper::resolveDumpSourceUsing(null);
    }
}

class CliDumperModel extends Model
{
}
