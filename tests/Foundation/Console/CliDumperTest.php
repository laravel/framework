<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\CliDumper;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class CliDumperTest extends TestCase
{
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

    public function testUnresolvableSource()
    {
        CliDumper::resolveDumpSourceUsing(fn () => null);

        $output = $this->dump('string');

        $expected = "\"string\"\n";

        $this->assertSame($expected, $output);
    }

    protected function dump($value)
    {
        $output = new BufferedOutput();
        $dumper = new CliDumper($output, '/my-work-directory');

        $cloner = tap(new VarCloner())->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper->dumpWithSource($cloner->cloneVar($value));

        return $output->fetch();
    }

    protected function tearDown(): void
    {
        CliDumper::resolveDumpSourceUsing(null);
    }
}
