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
     * The columns to eager load on every query.
     *
     * @var array
     */
    protected $withColumns = [];

    /**
     * Get the columns to eager load on every query.
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        return $this->withColumns;
    }
}
