<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphOneToSelectTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphOneToSelectTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->decimal('fund_size',30,5)->nullable();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('org_type')->nullable();
        });

        /** @var Organization $org */
        $org = Organization::query()->create();
        $fundExtension = FundExtension::query()->create(['org_id' => $org->getKey()]);
        $org->extension()->associate($fundExtension)->save();

        $org = Organization::query()->create();
        $orgExtension = OrgExtension::query()->create(['org_id' => $org->getKey()]);
        $org->extension()->associate($orgExtension)->save();
    }

    public function testSelect()
    {
        $organizations = Organization::with('extension:org_id')->get();

        $this->assertInstanceOf(FundExtension::class, $organizations[0]->extension);
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->extension->getAttributes());

        $this->assertInstanceOf(OrgExtension::class, $organizations[1]->extension);
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->extension->getAttributes());
    }

    public function testSelectRaw()
    {
        $organizations = Organization::with(['extension' => function ($query) {
            $query->selectRaw('org_id');
        }])->get();

        $this->assertInstanceOf(FundExtension::class, $organizations[0]->extension);
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->extension->getAttributes());

        $this->assertInstanceOf(OrgExtension::class, $organizations[1]->extension);
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->extension->getAttributes());
    }

    public function testSelectSub()
    {
        $organizations = Organization::with(['extension' => function ($query) {
            $query->selectSub(function ($query) {
                $query->select('org_id');
            }, 'org_id');
        }])->get();

        $this->assertInstanceOf(FundExtension::class, $organizations[0]->extension);
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->extension->getAttributes());

        $this->assertInstanceOf(OrgExtension::class, $organizations[1]->extension);
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->extension->getAttributes());
    }

    public function testAddSelect()
    {
        $organizations = Organization::with(['extension' => function ($query) {
            $query->addSelect('org_id');
        }])->get();

        $this->assertInstanceOf(FundExtension::class, $organizations[0]->extension);
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->extension->getAttributes());

        $this->assertInstanceOf(OrgExtension::class, $organizations[1]->extension);
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->extension->getAttributes());
    }

    public function testLazyLoading()
    {
        $organization = Organization::first();
        $entension = $organization->extension()->select('org_id')->first();

        $this->assertEquals(['org_id' => 1], $entension->getAttributes());
    }
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

}

class OrgExtension extends Model
{
    protected $guarded = [];
}

