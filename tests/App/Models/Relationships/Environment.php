<?php

namespace Illuminate\Tests\App\Models\Relationships;

class Environment extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->hasMany(Deployment::class, 'env_id', 'e_id');
    }

    public function metadata()
    {
        return $this->hasOne(MetaData::class, 'env_id', 'e_id');
    }
}
