<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\Concerns\InteractsWithModel;

class FoundationInteractsWithModel extends TestCase
{
    use InteractsWithModel;

    public function testModelUsesSoftDeletesTrait()
    {
        $this->assertSoftDeletes(SoftDeletesTestUser::class);
    }
}

class SoftDeletesTestUser extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
}
