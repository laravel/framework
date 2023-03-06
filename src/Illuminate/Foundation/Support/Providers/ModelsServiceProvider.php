<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ModelsServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, string>
     */
    protected $enforcedMorphMap = [];

    /**
     * @var array<string, string>
     */
    protected $morphMap = [];

    /**
     * Boots application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->enforcedMorphMap) {
            Relation::enforceMorphMap($this->enforcedMorphMap);
        } else {
            Relation::morphMap($this->morphMap);

            Relation::requireMorphMap($this->requireMorphMap());
        }

        Model::preventLazyLoading($this->preventLazyLoading());

        if (method_exists($this, 'onLazyLoadingViolation')) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                $this->onLazyLoadingViolation($model, $relation);
            });
        }

        Model::preventSilentlyDiscardingAttributes($this->preventSilentlyDiscardingAttributes());

        if (method_exists($this, 'onDiscardedAttributeViolation')) {
            Model::handleDiscardedAttributeViolationUsing(function (Model $model, $keys) {
                $this->onDiscardedAttributeViolation($model, $keys);
            });
        }

        Model::preventAccessingMissingAttributes($this->preventAccessingMissingAttributes());

        if (method_exists($this, 'onAccessingMissingAttributes')) {
            Model::handleMissingAttributeViolationUsing(function (Model $model, $keys) {
                $this->onAccessingMissingAttributes($model, $keys);
            });
        }
    }

    /**
     * Indicates if the models must require morph map.
     *
     * @return bool
     */
    protected function requireMorphMap()
    {
        return false;
    }

    /**
     * Indicates if the models must prevent lazy loading.
     *
     * @return bool
     */
    protected function preventLazyLoading()
    {
        return false;
    }

    /**
     * Indicates if the models must prevent silently discarding of attributes.
     *
     * @return bool
     */
    protected function preventSilentlyDiscardingAttributes()
    {
        return false;
    }

    /**
     * Indicates if the models must prevent accessing missing attributes.
     *
     * @return bool
     */
    protected function preventAccessingMissingAttributes()
    {
        return false;
    }
}
