<?php

namespace Illuminate\Tests\App\Models\Relationships;

class ClassicProject extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->hasManyThrough(
            Deployment::class,
            Environment::class,
            'pro_id',
            'env_id',
            'p_id',
            'e_id',
        );
    }

    public function environmentData()
    {
        return $this->hasManyThrough(
            Metadata::class,
            Environment::class,
            'pro_id',
            'env_id',
            'p_id',
            'e_id',
        );
    }
}
