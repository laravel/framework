<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\Post;
use Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\PostTagPivot;
use Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\TagWithCustomPivot;
use function dd;

class EloquentCustomPivotTableTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('collaborators', function (Blueprint $table) {
            $table->integer('eloquent_custom_pivot_table_user_id');
            $table->integer('eloquent_custom_pivot_table_project_id');
        });
    }

    public function testCustomTableIsUsedWhenAttaching()
    {
        $user = EloquentCustomPivotTableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $project = EloquentCustomPivotTableProject::forceCreate([
            'name' => 'Test EloquentCustomPivotTableProject',
        ]);

        $project->collaborators()->attach([$user->id]);


        $this->assertSame('collaborators', $project->collaborators()->getTable());

        //The next line throws an error
        $this->assertInstanceOf(CustomPivotTableTestCollaborator::class, $project->collaborators[0]->pivot);

        $this->assertEquals([
            'eloquent_custom_pivot_table_user_id' => '1',
            'eloquent_custom_pivot_table_project_id' => '1',
        ], $project->collaborators[0]->toArray());
    }
}

class EloquentCustomPivotTableUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
}

class EloquentCustomPivotTableProject extends Model
{
    public $table = 'projects';
    public $timestamps = false;

    public function collaborators()
    {
        return $this->belongsToMany(
            EloquentCustomPivotTableUser::class
        )->using(CustomPivotTableTestCollaborator::class);
    }
}

class CustomPivotTableTestCollaborator extends Pivot
{
    public $timestamps = false;

    protected $table = 'collaborators';
}
