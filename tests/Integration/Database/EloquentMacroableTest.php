<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\Macroable;
use Orchestra\Testbench\TestCase;

class EloquentMacroableTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tables
        Schema::create('test_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Register the service provider to define the macro
        $this->app->register(TestMacroServiceProvider::class);
    }

    public function test_create_static_method_fails_with_macroable()
    {
        // This test demonstrates the conflict between Macroable and Eloquent
        // We expect BadMethodCallException because the Macroable trait intercepts
        // the create call and doesn't find it as a defined macro
        
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\Tests\Integration\Database\TestCompany::create does not exist');
        
        // This should throw the exception because create isn't defined as a macro
        TestCompany::create(['name' => 'Test Company']);
    }

    public function test_query_first_fails_with_macroable()
    {
        // Even if we manually insert data, attempting to retrieve it will fail
        // because of the hydrate method conflict
        
        // Insert a record directly to bypass the create method
        $id = DB::table('test_companies')->insertGetId([
            'name' => 'Direct Insert Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->expectException(\BadMethodCallException::class);
        
        // This fails with: Method TestCompany::hydrate does not exist
        TestCompany::query()->first();
    }
}

class TestCompany extends Model
{
    use Macroable;
    
    protected $table = 'test_companies';
    protected $fillable = ['name'];
}

class TestMacroServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Nothing to register
    }
    
    public function boot()
    {
        TestCompany::macro('customMethod', function () {
            /** @var Model $this */
            return $this->getKey();
        });
    }
} 