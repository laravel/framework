<?php

namespace Illuminate\Tests\App\Models\Serialization;

use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Database\Eloquent\Attributes\Initialize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Queue\TraitBootsAndInitializersTest;

class TraitInitializedModel extends Model
{
    use TraitBootsAndInitializersTest;

    public static bool $bootedViaAttributeInClass = false;

    public bool $initializedViaAttributeInClass = false;

    #[Boot]
    public static function nonConventionalBootFunctionInClass()
    {
        static::addGlobalScope('booted_attr_in_class', function () {
        });
    }

    #[Initialize]
    public function nonConventionalInitFunctionInClass()
    {
        $this->initializedViaAttributeInClass = ! $this->initializedViaAttributeInClass;
    }
}
