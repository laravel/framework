<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphOneToEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOneTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphOneToEagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropIfExists('org_extensions');
        Schema::dropIfExists('fund_extensions');
        Schema::dropIfExists('organizations');
        Schema::create('org_extensions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->decimal('valuation', 30, 5)->nullable();
            $table->timestamps();
        });

        Schema::create('fund_extensions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->unsignedInteger('user_id');
            $table->decimal('fund_size',30,5)->nullable();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('org_type')->nullable();
        });

        $user = User::create();

        /** @var Organization $org */
        $org = Organization::query()->create();
        $fundExt = tap((new FundExtension(['org_id' => $org->getKey()]))->user()->associate($user))->save();
        $org->extension()->associate($fundExt)->save();

        $org = Organization::query()->create();
        $orgExt = OrgExtension::query()->create(['org_id' => $org->getKey()]);
        $org->extension()->associate($orgExt)->save();
    }

    public function testWithMorphLoading()
    {
        $organizations = Organization::query()
            ->with(['extension' => function (MorphOneTo $morphOneTo) {
                $morphOneTo->morphWith([FundExtension::class => ['user']]);
            }])
            ->get();

        $this->assertTrue($organizations[0]->relationLoaded('extension'));
        $this->assertTrue($organizations[0]->extension->relationLoaded('user'));
        $this->assertTrue($organizations[1]->relationLoaded('extension'));
    }

    public function testWithMorphLoadingWithSingleRelation()
    {
        $organizations = Organization::query()
            ->with(['extension' => function (MorphOneTo $morphOneTo) {
                $morphOneTo->morphWith([FundExtension::class => 'user']);
            }])
            ->get();

        $this->assertTrue($organizations[0]->relationLoaded('extension'));
        $this->assertTrue($organizations[0]->extension->relationLoaded('user'));
    }
}

class User extends Model
{
    public $timestamps = false;
}

class Organization extends Model
{
    public $timestamps = false;

    public function extension()
    {
        return $this->morphOneTo('extension', 'org_type', 'id', 'org_id');
    }
}

class FundExtension extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class OrgExtension extends Model
{
    protected $guarded = [];
}
