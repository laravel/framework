<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\Constraints\UsesSoftDeletesTrait;

trait InteractsWithModel
{
    /**
     * Assert that a model uses the SoftDelete trait.
     *
     * @param  string $model
     * @return $this
     */
    protected function assertSoftDeletes($model)
    {
        $this->assertThat(
            new $model, new UsesSoftDeletesTrait($model)
        );

        return $this;
    }
}
