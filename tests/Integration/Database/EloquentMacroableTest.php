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

        Schema::create('test_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('test_companies');
            $table->string('name');
            $table->timestamps();
        });

        // Register the service provider to define the macro
        $this->app->register(TestMacroServiceProvider::class);
    }

    public function test_create_static_method_works_with_macroable()
    {
        // This test verifies that static methods like create() continue to work
        // even when the Macroable trait is used
        
        $company = TestCompany::create(['name' => 'Test Company']);
        
        $this->assertInstanceOf(TestCompany::class, $company);
        $this->assertEquals('Test Company', $company->name);
        $this->assertTrue($company->exists);
    }

    public function test_query_first_works_with_macroable()
    {
        // This test verifies that eloquent methods for retrieving models from the database
        // work correctly even when the Macroable trait is used
        
        // Create a company using the create method
        TestCompany::create(['name' => 'First Company']);
        
        // Retrieve it using first()
        $company = TestCompany::query()->first();
        
        $this->assertInstanceOf(TestCompany::class, $company);
        $this->assertEquals('First Company', $company->name);
    }
    
    public function test_custom_macro_works()
    {
        // This test verifies that custom macros work with model instances
        
        $company = TestCompany::create(['name' => 'Macro Test Company']);
        
        // Use the custom macro
        $result = $company->getCompanyDetails();
        
        $this->assertEquals('Macro Test Company (ID: 1)', $result);
    }
    
    public function test_static_macro_works()
    {
        // This test verifies that static macros work
        
        $result = TestCompany::findByName('Static Macro Company');
        
        $this->assertNull($result); // Should be null since we haven't created this company
        
        // Create the company and try again
        TestCompany::create(['name' => 'Static Macro Company']);
        
        $result = TestCompany::findByName('Static Macro Company');
        $this->assertInstanceOf(TestCompany::class, $result);
        $this->assertEquals('Static Macro Company', $result->name);
    }
    
    public function test_both_eloquent_and_macros_work_together()
    {
        // This test verifies the integration of Eloquent functionality and macros
        
        // Create a company and its team
        $company = TestCompany::create(['name' => 'Integrated Company']);
        $team = $company->teams()->create(['name' => 'Integrated Team']);
        
        // Retrieve the company and use both Eloquent methods and macros
        $retrieved = TestCompany::find($company->id);
        $this->assertEquals('Integrated Company', $retrieved->name);
        
        // Use the custom macro
        $details = $retrieved->getCompanyDetails();
        $this->assertEquals('Integrated Company (ID: 1)', $details);
        
        // Use the relationship
        $this->assertCount(1, $retrieved->teams);
        $this->assertEquals('Integrated Team', $retrieved->teams->first()->name);
    }
}

class TestCompany extends Model
{
    use Macroable;
    
    protected $table = 'test_companies';
    protected $fillable = ['name'];
    
    public function teams()
    {
        return $this->hasMany(TestTeam::class, 'company_id');
    }
}

class TestTeam extends Model
{
    protected $table = 'test_teams';
    protected $fillable = ['company_id', 'name'];
    
    public function company()
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }
}

class TestMacroServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Nothing to register
    }
    
    public function boot()
    {
        // Instance macro
        TestCompany::macro('getCompanyDetails', function () {
            /** @var TestCompany $this */
            return $this->name . ' (ID: ' . $this->id . ')';
        });
        
        // Static macro
        TestCompany::macro('findByName', function ($name) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = TestCompany::query();
            return $query->where('name', $name)->first();
        });
    }
} 