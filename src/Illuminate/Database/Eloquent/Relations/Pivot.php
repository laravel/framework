<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class Pivot extends Model
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The pivot table additional columns to retrieve.
     *
     * @var array
     */
    protected $withPivot = [];

    /**
     * Get the additional columns on the pivot table to retrieve.
     *
     * @return array
     */
    public function getWithPivot()
    {
        return $this->withPivot;
    }
}
