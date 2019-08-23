<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class EloquentBelongsToManyUpdateTouchesTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rank')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('brand_id');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->timestamps();
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->timestamps();
        });

        $this->seedData();
    }

    protected function seedData()
    {
        $brand = EloquentBelongsToManyUpdateTouchesTestBrand::query()->create();
        $product = EloquentBelongsToManyUpdateTouchesTestProduct::query()->create(['sku' => 'apple']);

        $category = EloquentBelongsToManyUpdateTouchesTestCategory::query()->create([
            'name' => 'fruits',
            'brand_id' => $brand->id,
        ]);

        $category->products()->attach($product);
    }

    public function testBelongsToTouches()
    {
        EloquentBelongsToManyUpdateTouchesTestBrand::observe(new EloquentBelongsToManyUpdateTouchesTestBrandObserve);

        $category = EloquentBelongsToManyUpdateTouchesTestCategory::query()->first();
        $category->name = 'toys';
        $category->save();

        $this->assertEquals(EloquentBelongsToManyUpdateTouchesTestBrandObserve::$hasFiredUpdated, false);
        $this->assertEquals(EloquentBelongsToManyUpdateTouchesTestBrandObserve::$hasFiredSaved, true);
    }

    public function testBelongsToManyTouches()
    {
        EloquentBelongsToManyUpdateTouchesTestProduct::observe(new EloquentBelongsToManyUpdateTouchesTestProductObserve);

        $category = EloquentBelongsToManyUpdateTouchesTestCategory::query()->first();
        $category->name = 'phones';
        $category->save();

        $this->assertEquals(EloquentBelongsToManyUpdateTouchesTestProductObserve::$hasFiredUpdated, false);
        $this->assertEquals(EloquentBelongsToManyUpdateTouchesTestProductObserve::$hasFiredSaved, true);
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Schema::dropIfExists('brands');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('products');

        parent::tearDown();
    }
}

class EloquentBelongsToManyUpdateTouchesTestBrand extends Model
{
    protected $table = 'brands';
    protected $fillable = ['id', 'rank'];
}

class EloquentBelongsToManyUpdateTouchesTestProduct extends Model
{
    protected $table = 'products';
    protected $fillable = ['id', 'sku'];
}

class EloquentBelongsToManyUpdateTouchesTestProductCategoryPivot extends Pivot
{
    protected $table = 'product_category';
    public $incrementing = true;
}

class EloquentBelongsToManyUpdateTouchesTestCategory extends Model
{
    protected $table = 'categories';
    protected $fillable = ['id', 'name', 'brand_id'];

    protected $touches = ['brand', 'products'];

    public function brand(): Relation
    {
        return $this->belongsTo(EloquentBelongsToManyUpdateTouchesTestBrand::class, 'brand_id', 'id');
    }

    public function products(): Relation
    {
        return $this
            ->belongsToMany(
                EloquentBelongsToManyUpdateTouchesTestProduct::class,
                'product_category',
                'category_id',
                'product_id'
            )
            ->using(EloquentBelongsToManyUpdateTouchesTestProductCategoryPivot::class)
            ->withPivot('id', 'product_id', 'category_id')
            ->withTimestamps();
    }
}

class EloquentBelongsToManyUpdateTouchesTestBrandObserve
{
    public static $hasFiredUpdated = false;
    public static $hasFiredSaved = false;

    public function updated(EloquentBelongsToManyUpdateTouchesTestBrand $brand)
    {
        // TODO: Mock laravel/scout listener to do something. e.g. sync to ES if its driver is ElasticSearch
        self::$hasFiredUpdated = true;
    }

    public function saved(EloquentBelongsToManyUpdateTouchesTestBrand $brand)
    {
        // TODO: Mock laravel/scout listener to do something. e.g. sync to ES if its driver is ElasticSearch
        self::$hasFiredSaved = true;
    }
}

class EloquentBelongsToManyUpdateTouchesTestProductObserve
{
    public static $hasFiredUpdated = false;
    public static $hasFiredSaved = false;

    public function updated(EloquentBelongsToManyUpdateTouchesTestProduct $product)
    {
        // TODO: Mock laravel/scout listener to do something. e.g. sync to ES if its driver is ElasticSearch
        self::$hasFiredUpdated = true;
    }

    public function saved(EloquentBelongsToManyUpdateTouchesTestProduct $product)
    {
        // TODO: Mock laravel/scout listener to do something. e.g. sync to ES if its driver is ElasticSearch
        self::$hasFiredSaved = true;
    }
}
