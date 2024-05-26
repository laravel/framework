<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;

class EloquentModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->setAsGlobal();
        $db->bootEloquent();

        $schema = $db->schema();

        $schema->create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        $schema->create('item_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_id');
            $table->timestamps();
        });

        Eloquent::unguard();
    }

    public function testItCanEagerLoadAllRelationsUsingWithAll()
    {
        $item = Item::create();
        ItemDetail::create(['item_id' => $item->id]);
        ItemDetail::create(['item_id' => $item->id]);
    
        $itemWithRelations = Item::withAll()->find($item->id);
    
        $this->assertTrue($itemWithRelations->relationLoaded('itemDetails'), 'The itemDetails relation should be loaded');
        $this->assertCount(2, $itemWithRelations->itemDetails, 'There should be exactly 2 item details loaded');
    }
    

    public function testItCanEagerLoadAllRelationsExceptSpecifiedOnesWithExcept()
    {
        $item = Item::create();
        ItemDetail::create(['item_id' => $item->id]);
        ItemDetail::create(['item_id' => $item->id]);

        $itemWithoutDetails = Item::except('itemDetails')->find($item->id);

        $this->assertFalse($itemWithoutDetails->relationLoaded('itemDetails'), 'The itemDetails relation should not be loaded');
    }

    public function testItCanEagerLoadAllRelationsExceptMultipleSpecifiedOnesWithExcept()
    {
        $item = Item::create();
        ItemDetail::create(['item_id' => $item->id]);
        ItemDetail::create(['item_id' => $item->id]);

        $itemWithoutRelations = Item::except(['itemDetails'])->find($item->id);

        $this->assertFalse($itemWithoutRelations->relationLoaded('itemDetails'), 'The itemDetails relation should not be loaded');
    }
}

class Item extends Eloquent
{
    public function itemDetails()
    {
        return $this->hasMany(ItemDetail::class, 'item_id');
    }
}

class ItemDetail extends Eloquent
{
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
