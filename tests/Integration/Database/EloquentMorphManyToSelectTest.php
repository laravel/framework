<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphManyToSelectTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphManyToSelectTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('org_deals');
        Schema::dropIfExists('fund_deals');
        Schema::dropIfExists('organizations');
        Schema::create('org_deals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->string('investment')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_deals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('org_id');
            $table->string('call')->nullable();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('org_type')->nullable();
        });

        /** @var Organization $org */
        $org = Organization::query()->create();
        $fundDeal = FundDeal::query()->create(['org_id' => $org->getKey()]);
        $org->deals()->associate($fundDeal)->save();

        $org = Organization::query()->create();
        $orgDeal = OrgDeal::query()->create(['org_id' => $org->getKey()]);
        $org->deals()->associate($orgDeal)->save();
    }

    public function testSelect()
    {
        $organizations = Organization::with('deals:org_id')->get();
        $this->assertInstanceOf(Collection::class, $organizations[0]->deals);
        $this->assertInstanceOf(FundDeal::class, $organizations[0]->deals->first());
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->deals->first()->getAttributes());

        $this->assertInstanceOf(Collection::class, $organizations[1]->deals);
        $this->assertInstanceOf(OrgDeal::class, $organizations[1]->deals->first());
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->deals->first()->getAttributes());
    }

    public function testSelectRaw()
    {
        $organizations = Organization::with(['deals' => function ($query) {
            $query->selectRaw('org_id');
        }])->get();

        $this->assertInstanceOf(Collection::class, $organizations[0]->deals);
        $this->assertInstanceOf(FundDeal::class, $organizations[0]->deals->first());
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->deals->first()->getAttributes());

        $this->assertInstanceOf(Collection::class, $organizations[1]->deals);
        $this->assertInstanceOf(OrgDeal::class, $organizations[1]->deals->first());
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->deals->first()->getAttributes());
    }

    public function testSelectSub()
    {
        $organizations = Organization::with(['deals' => function ($query) {
            $query->selectSub(function ($query) {
                $query->select('org_id');
            }, 'org_id');
        }])->get();

        $this->assertInstanceOf(Collection::class, $organizations[0]->deals);
        $this->assertInstanceOf(FundDeal::class, $organizations[0]->deals->first());
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->deals->first()->getAttributes());

        $this->assertInstanceOf(Collection::class, $organizations[1]->deals);
        $this->assertInstanceOf(OrgDeal::class, $organizations[1]->deals->first());
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->deals->first()->getAttributes());
    }

    public function testAddSelect()
    {
        $organizations = Organization::with(['deals' => function ($query) {
            $query->addSelect('org_id');
        }])->get();

        $this->assertInstanceOf(Collection::class, $organizations[0]->deals);
        $this->assertInstanceOf(FundDeal::class, $organizations[0]->deals->first());
        $this->assertEquals(['org_id' => $organizations[0]->getKey()], $organizations[0]->deals->first()->getAttributes());

        $this->assertInstanceOf(Collection::class, $organizations[1]->deals);
        $this->assertInstanceOf(OrgDeal::class, $organizations[1]->deals->first());
        $this->assertEquals(['org_id' => $organizations[1]->getKey()], $organizations[1]->deals->first()->getAttributes());
    }

    public function testLazyLoading()
    {
        $organization = Organization::first();
        $fundDeals = $organization->deals()->select('org_id')->get();
        $this->assertInstanceOf(Collection::class, $fundDeals);
        $this->assertInstanceOf(FundDeal::class, $fundDeals->first());
        $this->assertEquals(['org_id' => $organization->getKey()], $fundDeals->first()->getAttributes());

        $organization = Organization::query()->limit(1)->offset(1)->first();
        $orgDeals = $organization->deals()->select('org_id')->get();
        $this->assertInstanceOf(Collection::class, $orgDeals);
        $this->assertInstanceOf(OrgDeal::class, $orgDeals->first());
        $this->assertEquals(['org_id' => $organization->getKey()], $orgDeals->first()->getAttributes());
    }
}

class Organization extends Model
{
    public $timestamps = false;

    public function deals()
    {
        return $this->morphManyTo('deals', 'org_type', 'id', 'org_id');
    }
}

class FundDeal extends Model
{
    protected $guarded = [];
}

class OrgDeal extends Model
{
    protected $guarded = [];
}
