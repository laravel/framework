<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelWithoutEventsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('auto_filled_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable()->unique();
            $table->text('project')->nullable();
        });

        Schema::create('auto_filled_grand_parents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        Schema::create('auto_filled_parents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('grand_parent_id')->nullable();
            $table->string('name')->nullable();
        });

        Schema::create('auto_filled_children', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('title')->nullable()->unique();
            $table->text('project')->nullable();
        });

        Schema::create('auto_filled_related', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable()->unique();
            $table->text('project')->nullable();
        });

        Schema::create('auto_filled_pivot', function (Blueprint $table) {
            $table->integer('parent_id');
            $table->integer('related_id');
        });
    }

    public function testWithoutEventsRegistersBootedListenersForLater()
    {
        $model = AutoFilledModel::withoutEvents(function () {
            return AutoFilledModel::create();
        });

        $this->assertNull($model->project);

        $model->save();

        $this->assertSame('Laravel', $model->project);
    }

    public function testFirstOrCreateQuietly()
    {
        AutoFilledModel::$eventsTriggered = [];

        $model = AutoFilledModel::firstOrCreateQuietly(
            ['name' => 'First'],
            ['project' => 'Custom'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame('Custom', $model->project);
        $this->assertDatabaseHas('auto_filled_models', [
            'name' => 'First',
            'project' => 'Custom',
        ]);

        $existing = AutoFilledModel::firstOrCreateQuietly(
            ['name' => 'First'],
            ['project' => 'Different'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame($model->id, $existing->id);
        $this->assertSame('Custom', $existing->project);
    }

    public function testCreateOrFirstQuietly()
    {
        AutoFilledModel::$eventsTriggered = [];

        $model = AutoFilledModel::createOrFirstQuietly(
            ['name' => 'CreateOrFirst'],
            ['project' => 'Custom'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame('Custom', $model->project);
        $this->assertDatabaseHas('auto_filled_models', [
            'name' => 'CreateOrFirst',
            'project' => 'Custom',
        ]);

        $existing = AutoFilledModel::createOrFirstQuietly(
            ['name' => 'CreateOrFirst'],
            ['project' => 'Different'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame($model->id, $existing->id);
        $this->assertSame('Custom', $existing->project);
    }

    public function testUpdateOrCreateQuietly()
    {
        AutoFilledModel::$eventsTriggered = [];

        $model = AutoFilledModel::updateOrCreateQuietly(
            ['name' => 'UpdateOrCreate'],
            ['project' => 'Initial'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame('Initial', $model->project);
        $this->assertDatabaseHas('auto_filled_models', [
            'name' => 'UpdateOrCreate',
            'project' => 'Initial',
        ]);

        $updated = AutoFilledModel::updateOrCreateQuietly(
            ['name' => 'UpdateOrCreate'],
            ['project' => 'Updated'],
        );

        $this->assertEmpty(AutoFilledModel::$eventsTriggered);
        $this->assertSame($model->id, $updated->id);
        $this->assertSame('Updated', $updated->project);
        $this->assertSame('Updated', $model->fresh()->project);
    }

    public function testHasOneOrManyQuietlyMethods()
    {
        $parent = AutoFilledParent::create(['name' => 'Parent']);

        AutoFilledChild::$eventsTriggered = [];

        // firstOrCreateQuietly
        $child1 = $parent->children()->firstOrCreateQuietly(
            ['title' => 'Child 1'],
            ['project' => 'Custom 1'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Custom 1', $child1->project);
        $this->assertSame($parent->id, $child1->parent_id);

        $existingChild1 = $parent->children()->firstOrCreateQuietly(
            ['title' => 'Child 1'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child1->id, $existingChild1->id);

        // createOrFirstQuietly
        $child2 = $parent->children()->createOrFirstQuietly(
            ['title' => 'Child 2'],
            ['project' => 'Custom 2'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Custom 2', $child2->project);
        $this->assertSame($parent->id, $child2->parent_id);

        $existingChild2 = $parent->children()->createOrFirstQuietly(
            ['title' => 'Child 2'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child2->id, $existingChild2->id);

        // updateOrCreateQuietly (create)
        $child3 = $parent->children()->updateOrCreateQuietly(
            ['title' => 'Child 3'],
            ['project' => 'Initial 3'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Initial 3', $child3->project);
        $this->assertSame($parent->id, $child3->parent_id);

        // updateOrCreateQuietly (update)
        $updatedChild3 = $parent->children()->updateOrCreateQuietly(
            ['title' => 'Child 3'],
            ['project' => 'Updated 3'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child3->id, $updatedChild3->id);
        $this->assertSame('Updated 3', $updatedChild3->project);
        $this->assertSame('Updated 3', $child3->fresh()->project);
    }

    public function testBelongsToManyQuietlyMethods()
    {
        $parent = AutoFilledParent::create(['name' => 'Parent']);

        AutoFilledRelated::$eventsTriggered = [];

        // firstOrCreateQuietly
        $rel1 = $parent->related()->firstOrCreateQuietly(
            ['name' => 'Related 1'],
            ['project' => 'Custom 1'],
        );

        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame('Custom 1', $rel1->project);
        $this->assertNotNull($parent->related()->where('auto_filled_related.id', $rel1->id)->first());

        $existingRel1 = $parent->related()->firstOrCreateQuietly(
            ['name' => 'Related 1'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame($rel1->id, $existingRel1->id);

        // createOrFirstQuietly
        $rel2 = $parent->related()->createOrFirstQuietly(
            ['name' => 'Related 2'],
            ['project' => 'Custom 2'],
        );

        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame('Custom 2', $rel2->project);
        $this->assertNotNull($parent->related()->where('auto_filled_related.id', $rel2->id)->first());

        $existingRel2 = $parent->related()->createOrFirstQuietly(
            ['name' => 'Related 2'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame($rel2->id, $existingRel2->id);

        // updateOrCreateQuietly (create)
        $rel3 = $parent->related()->updateOrCreateQuietly(
            ['name' => 'Related 3'],
            ['project' => 'Initial 3'],
        );

        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame('Initial 3', $rel3->project);
        $this->assertNotNull($parent->related()->where('auto_filled_related.id', $rel3->id)->first());

        // updateOrCreateQuietly (update)
        $updatedRel3 = $parent->related()->updateOrCreateQuietly(
            ['name' => 'Related 3'],
            ['project' => 'Updated 3'],
        );

        $this->assertEmpty(AutoFilledRelated::$eventsTriggered);
        $this->assertSame($rel3->id, $updatedRel3->id);
        $this->assertSame('Updated 3', $updatedRel3->project);
        $this->assertSame('Updated 3', $rel3->fresh()->project);
    }

    public function testHasOneOrManyThroughQuietlyMethods()
    {
        $grandParent = AutoFilledGrandParent::create(['name' => 'Grand Parent']);
        $parent = AutoFilledParent::create(['name' => 'Parent', 'grand_parent_id' => $grandParent->id]);

        AutoFilledChild::$eventsTriggered = [];

        // firstOrCreateQuietly
        $child1 = $grandParent->children()->firstOrCreateQuietly(
            ['parent_id' => $parent->id, 'title' => 'Through Child 1'],
            ['project' => 'Custom 1'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Custom 1', $child1->project);

        $existingChild1 = $grandParent->children()->firstOrCreateQuietly(
            ['title' => 'Through Child 1'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child1->id, $existingChild1->id);

        // createOrFirstQuietly
        $child2 = $grandParent->children()->createOrFirstQuietly(
            ['parent_id' => $parent->id, 'title' => 'Through Child 2'],
            ['project' => 'Custom 2'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Custom 2', $child2->project);

        $existingChild2 = $grandParent->children()->createOrFirstQuietly(
            ['parent_id' => $parent->id, 'title' => 'Through Child 2'],
            ['project' => 'Different'],
        );
        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child2->id, $existingChild2->id);

        // updateOrCreateQuietly (create)
        $child3 = $grandParent->children()->updateOrCreateQuietly(
            ['parent_id' => $parent->id, 'title' => 'Through Child 3'],
            ['project' => 'Initial 3'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame('Initial 3', $child3->project);

        // updateOrCreateQuietly (update)
        $updatedChild3 = $grandParent->children()->updateOrCreateQuietly(
            ['title' => 'Through Child 3'],
            ['project' => 'Updated 3'],
        );

        $this->assertEmpty(AutoFilledChild::$eventsTriggered);
        $this->assertSame($child3->id, $updatedChild3->id);
        $this->assertSame('Updated 3', $updatedChild3->project);
        $this->assertSame('Updated 3', $child3->fresh()->project);
    }
}

class AutoFilledGrandParent extends Model
{
    public $table = 'auto_filled_grand_parents';
    public $timestamps = false;
    protected $guarded = [];

    public function children()
    {
        return $this->hasManyThrough(AutoFilledChild::class, AutoFilledParent::class, 'grand_parent_id', 'parent_id');
    }
}

class AutoFilledParent extends Model
{
    public $table = 'auto_filled_parents';
    public $timestamps = false;
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(AutoFilledChild::class, 'parent_id');
    }

    public function related()
    {
        return $this->belongsToMany(AutoFilledRelated::class, 'auto_filled_pivot', 'parent_id', 'related_id');
    }
}

class AutoFilledChild extends Model
{
    public $table = 'auto_filled_children';
    public $timestamps = false;
    protected $guarded = [];
    public static $eventsTriggered = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            static::$eventsTriggered[] = 'saving';
            $model->project = 'Laravel';
        });

        static::creating(function ($model) {
            static::$eventsTriggered[] = 'creating';
        });

        static::updating(function ($model) {
            static::$eventsTriggered[] = 'updating';
        });
    }
}

class AutoFilledRelated extends Model
{
    public $table = 'auto_filled_related';
    public $timestamps = false;
    protected $guarded = [];
    public static $eventsTriggered = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            static::$eventsTriggered[] = 'saving';
            $model->project = 'Laravel';
        });

        static::creating(function ($model) {
            static::$eventsTriggered[] = 'creating';
        });

        static::updating(function ($model) {
            static::$eventsTriggered[] = 'updating';
        });
    }
}

class AutoFilledModel extends Model
{
    public $table = 'auto_filled_models';
    public $timestamps = false;
    protected $guarded = [];
    public static $eventsTriggered = [];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            static::$eventsTriggered[] = 'saving';
            $model->project = 'Laravel';
        });

        static::creating(function ($model) {
            static::$eventsTriggered[] = 'creating';
        });

        static::updating(function ($model) {
            static::$eventsTriggered[] = 'updating';
        });
    }
}
