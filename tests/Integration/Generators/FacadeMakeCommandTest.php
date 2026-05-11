<?php

namespace Illuminate\Tests\Integration\Generators;

class FacadeMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Support/Facades/Audio.php',
        'app/Support/Facades/Commerce/Customers.php',
        'app/Support/Facades/Commerce/Orders.php',
        'app/Support/Facades/Music.php',
        'app/Support/Facades/Podcasts.php',
        'app/Support/Facades/Stringable.php',
        'app/Support/Facades/Strings.php',
    ];

    public function testItCanGenerateFacadeFile()
    {
        $this->artisan('make:facade', ['name' => 'Podcasts'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use Illuminate\Support\Facades\Facade;',
            'class Podcasts extends Facade',
            "return 'podcasts';",
        ], 'app/Support/Facades/Podcasts.php');
    }

    public function testItCanGenerateFacadeWithAliasAccessor()
    {
        $this->artisan('make:facade', ['name' => 'Commerce/Customers', '--accessor' => 'commerce.customers'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades\Commerce;',
            'use Illuminate\Support\Facades\Facade;',
            'class Customers extends Facade',
            "return 'commerce.customers';",
        ], 'app/Support/Facades/Commerce/Customers.php');
    }

    public function testItCanGenerateFacadeWithClassAccessor()
    {
        $this->artisan('make:facade', ['name' => 'Audio', '--accessor' => 'App\\Services\\AudioService'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use App\Services\AudioService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin AudioService',
            'class Audio extends Facade',
            'return AudioService::class;',
        ], 'app/Support/Facades/Audio.php');
    }

    public function testItCanGenerateFacadeFileWithTargetOption()
    {
        $this->artisan('make:facade', [
            'name' => 'Strings',
            '--target' => 'Illuminate\Support\Stringable',
        ])->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use Illuminate\Support\Stringable;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin Stringable',
            'class Strings extends Facade',
            'return Stringable::class;',
        ], 'app/Support/Facades/Strings.php');
    }

    public function testItCanGenerateFacadeWithClassAccessorAndTarget()
    {
        $this->artisan('make:facade', [
            'name' => 'Customers',
            '--accessor' => 'commerce.customers',
            '--target' => 'App\\Services\\Commerce\\CustomerService',
        ])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use App\Services\Commerce\CustomerService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin CustomerService',
            'class Customers extends Facade',
            "return 'commerce.customers';",
        ], 'app/Support/Facades/Customers.php');
    }

    public function testItCanDetectServiceForFacade()
    {
        $this->artisan('make:facade', ['name' => 'Commerce/Orders'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades\Commerce;',
            'use App\Services\Commerce\OrdersService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin OrdersService',
            'class Orders extends Facade',
            'return OrdersService::class;',
        ], 'app/Support/Facades/Commerce/Orders.php');
    }

    public function testItCanAliasDetectedServiceForFacade()
    {
        $this->artisan('make:facade', ['name' => 'Music'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use App\Services\Music as MusicService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin MusicService',
            'class Music extends Facade',
            'return MusicService::class;',
        ], 'app/Support/Facades/Music.php');
    }

    public function testItCanAliasExplicitServiceForFacade()
    {
        $this->artisan('make:facade', [
            'name' => 'Stringable',
            '--target' => 'Illuminate\Support\Stringable',
        ])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use Illuminate\Support\Stringable as StringableService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin StringableService',
            'class Stringable extends Facade',
            'return StringableService::class;',
        ], 'app/Support/Facades/Stringable.php');
    }

    public function testItCanAliasExplicitServiceForFacadeWithAccessor()
    {
        $this->artisan('make:facade', [
            'name' => 'Stringable',
            '--accessor' => 'stringable',
            '--target' => 'Illuminate\Support\Stringable',
        ])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Support\Facades;',
            'use Illuminate\Support\Stringable as StringableService;',
            'use Illuminate\Support\Facades\Facade;',
            '@mixin StringableService',
            'class Stringable extends Facade',
            "return 'stringable';",
        ], 'app/Support/Facades/Stringable.php');
    }
}

namespace App\Services;

class AudioService
{
}
class Music
{
}

namespace App\Services\Commerce;

class CustomerService
{
}
class OrdersService
{
}
