<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\ConfigFileMerger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigFileMergerTest extends TestCase
{
    public function testItAddsMissingTopLevelKeysWithTheirComments()
    {
        $existing = <<<'PHP'
<?php

return [

    /*
    | The driver.
    */

    'driver' => env('PACKAGE_DRIVER', 'redis'),

];

PHP;

        $incoming = <<<'PHP'
<?php

return [

    /*
    | The driver.
    */

    'driver' => env('PACKAGE_DRIVER', 'file'),

    /*
    | The timeout.
    */

    'timeout' => env('PACKAGE_TIMEOUT', 60), // seconds

];

PHP;

        $this->assertSame(<<<'PHP'
<?php

return [

    /*
    | The driver.
    */

    'driver' => env('PACKAGE_DRIVER', 'redis'),

    /*
    | The timeout.
    */

    'timeout' => env('PACKAGE_TIMEOUT', 60), // seconds

];

PHP, $this->merge($existing, $incoming));
    }

    public function testItAddsMissingNestedKeys()
    {
        $existing = <<<'PHP'
<?php

return [
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'custom',
        ],
    ],
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => [
                'name' => 'default',
            ],
        ],
        'file' => [
            'driver' => 'file',
        ],
    ],
];

PHP;

        $this->assertSame(<<<'PHP'
<?php

return [
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'custom',
            'lock_connection' => [
                'name' => 'default',
            ],
        ],
        'file' => [
            'driver' => 'file',
        ],
    ],
];

PHP, $this->merge($existing, $incoming));
    }

    public function testItReturnsTheExistingFileUnchangedWhenNothingIsMissing()
    {
        $existing = <<<'PHP'
<?php

// My customized configuration...
return [
    'driver' => env('DRIVER', 'mine'), // changed
    'extra' => true,
    'options' => [
        'retries' => 5,
    ],
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'driver' => env('DRIVER', 'default'),
    'options' => [
        'retries' => 3,
    ],
];

PHP;

        $this->assertSame($existing, $this->merge($existing, $incoming));
    }

    public function testItDoesNotMergeListsOrReplaceNonArrayValues()
    {
        $existing = <<<'PHP'
<?php

return [
    'hosts' => [
        'one.test',
    ],
    'options' => null,
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'hosts' => [
        'one.test',
        'two.test',
    ],
    'options' => [
        'retries' => 3,
    ],
];

PHP;

        $this->assertSame($existing, $this->merge($existing, $incoming));
    }

    public function testItDoesNotMergeIntoArraysWithDynamicKeys()
    {
        $existing = <<<'PHP'
<?php

return [
    'options' => [
        Some::KEY => 1,
    ],
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'options' => [
        'key' => 1,
    ],
];

PHP;

        $this->assertSame($existing, $this->merge($existing, $incoming));
    }

    public function testItAddsATrailingCommaWhenTheLastEntryHasNone()
    {
        $existing = <<<'PHP'
<?php

return [
    'a' => 1 // one
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'a' => 1,
    'b' => 2,
];

PHP;

        $this->assertSame(<<<'PHP'
<?php

return [
    'a' => 1, // one
    'b' => 2,
];

PHP, $this->merge($existing, $incoming));
    }

    public function testItMergesIntoEmptyArrays()
    {
        $existing = <<<'PHP'
<?php

return [
    'options' => [],
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'options' => [
        'a' => 1,
        'b' => 2,
    ],
];

PHP;

        $this->assertSame(<<<'PHP'
<?php

return [
    'options' => [
        'a' => 1,
        'b' => 2,
    ],
];

PHP, $this->merge($existing, $incoming));
    }

    public function testItMergesSingleLineAndLongSyntaxArrays()
    {
        $existing = <<<'PHP'
<?php

return array(
    'options' => ['a' => 1],
);

PHP;

        $incoming = <<<'PHP'
<?php

return array(
    'options' => array('a' => 1, 'b' => array(2)),
    'other' => true,
);

PHP;

        $this->assertSame(<<<'PHP'
<?php

return array(
    'options' => ['a' => 1, 'b' => array(2)],
    'other' => true,
);

PHP, $this->merge($existing, $incoming));
    }

    public function testItAddsImportsReferencedByInsertedEntries()
    {
        $existing = <<<'PHP'
<?php

use Illuminate\Support\Arr;

return [
    'a' => Arr::first([1]),
];

PHP;

        $incoming = <<<'PHP'
<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Unused;
use Some\Package\Driver as PackageDriver;

return [
    'a' => Arr::first([1]),
    'b' => Str::slug('Laravel'),
    'c' => PackageDriver::class,
];

PHP;

        $this->assertSame(<<<'PHP'
<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Some\Package\Driver as PackageDriver;

return [
    'a' => Arr::first([1]),
    'b' => Str::slug('Laravel'),
    'c' => PackageDriver::class,
];

PHP, $this->merge($existing, $incoming));
    }

    public function testItAddsImportsWhenTheExistingFileHasNone()
    {
        $existing = "<?php\n\nreturn [\n    'a' => 1,\n];\n";

        $incoming = "<?php\n\nuse Illuminate\Support\Str;\n\nreturn [\n    'a' => 1,\n    'b' => Str::slug('x'),\n];\n";

        $this->assertSame(
            "<?php\n\nuse Illuminate\Support\Str;\n\nreturn [\n    'a' => 1,\n    'b' => Str::slug('x'),\n];\n",
            $this->merge($existing, $incoming),
        );
    }

    public function testItPreservesWindowsLineEndings()
    {
        $existing = "<?php\r\n\r\nreturn [\r\n    'a' => 1,\r\n];\r\n";

        $incoming = "<?php\n\nreturn [\n    'a' => 1,\n    'b' => [\n        'c' => 2,\n    ],\n];\n";

        $this->assertSame(
            "<?php\r\n\r\nreturn [\r\n    'a' => 1,\r\n    'b' => [\r\n        'c' => 2,\r\n    ],\r\n];\r\n",
            $this->merge($existing, $incoming),
        );
    }

    public function testItHandlesValuesContainingBracketsAndClosures()
    {
        $existing = <<<'PHP'
<?php

return [
    'a' => env('A', ['x' => 1]),
];

PHP;

        $incoming = <<<'PHP'
<?php

return [
    'a' => env('A', ['x' => 1]),
    'b' => fn ($value) => match ($value) { 1 => 'one', default => "{$value}" },
    'c' => 'd',
];

PHP;

        $this->assertSame(<<<'PHP'
<?php

return [
    'a' => env('A', ['x' => 1]),
    'b' => fn ($value) => match ($value) { 1 => 'one', default => "{$value}" },
    'c' => 'd',
];

PHP, $this->merge($existing, $incoming));
    }

    #[DataProvider('unmergeableFiles')]
    public function testItReturnsNullForFilesThatAreNotConfigurationFiles(string $file)
    {
        $config = "<?php\n\nreturn [\n    'a' => 1,\n];\n";

        $this->assertNull((new ConfigFileMerger)->merge($file, $config));
        $this->assertNull((new ConfigFileMerger)->merge($config, $file));
    }

    public static function unmergeableFiles()
    {
        return [
            'routes' => ["<?php\n\nuse Illuminate\Support\Facades\Route;\n\nRoute::get('/', fn () => 'hi');\n"],
            'migration' => ["<?php\n\nuse Illuminate\Database\Migrations\Migration;\n\nreturn new class extends Migration\n{\n    public function up(): void\n    {\n    }\n};\n"],
            'class' => ["<?php\n\nnamespace App;\n\nclass Thing\n{\n}\n"],
            'code before return' => ["<?php\n\n\$value = 1;\n\nreturn [\n    'a' => \$value,\n];\n"],
            'code after return' => ["<?php\n\nreturn [\n    'a' => 1,\n];\n\necho 'hi';\n"],
            'invalid php' => ["<?php\n\nreturn [\n    'a' => 1\n    'b' => 2,\n];\n"],
            'blade view' => ["<div>{{ \$name }}</div>\n"],
        ];
    }

    protected function merge(string $existing, string $incoming)
    {
        $merged = (new ConfigFileMerger)->merge($existing, $incoming);

        $this->assertNotNull($merged);

        return $merged;
    }
}
