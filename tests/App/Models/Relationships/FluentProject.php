<?php

namespace Illuminate\Tests\App\Models\Relationships;

class FluentProject extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->through($this->environments())->has(fn (Environment $env) => $env->deployments());
    }

    public function environmentData()
    {
        return $this->through($this->environments())->has(fn (Environment $env) => $env->metadata());
    }

    public function environments()
    {
        return $this->hasMany(Environment::class, 'pro_id', 'p_id');
    }
}
