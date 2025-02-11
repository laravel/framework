<?php

namespace Illuminate\Tests\Integration\Generators;

class FilterMakeCommandTest extends TestCase
{
    protected $files = ['app/Filters/Foo.php'];

    public function testItCanGenerateFilterFile()
    {
        $this->artisan('make:filter', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Filters;',
            'use Illuminate\Database\Eloquent\Builder;',
            'class Foo',
            'public static function apply(Builder $builder,$param)',
        ], 'app/Filters/Foo.php');
    }
}
