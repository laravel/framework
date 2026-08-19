<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotTestUser extends Model
{
    public $table = 'users';

    public function activeSubscriptions()
    {
        return $this->belongsToMany(PivotTestProject::class, 'subscriptions', 'user_id', 'project_id')
            ->withPivotValue('status', 'active')
            ->withPivot('status')
            ->using(PivotTestSubscription::class);
    }

    public function inactiveSubscriptions()
    {
        return $this->belongsToMany(PivotTestProject::class, 'subscriptions', 'user_id', 'project_id')
            ->withPivotValue('status', 'inactive')
            ->withPivot('status')
            ->using(PivotTestSubscription::class);
    }
}
