<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class DateRangeDoesNotOverlap implements ValidationRule
{
    /**
     * The table to check for overlaps.
     *
     * @var string
     */
    protected $table;

    /**
     * The column name for the start date.
     *
     * @var string
     */
    protected $startColumn;

    /**
     * The column name for the end date.
     *
     * @var string
     */
    protected $endColumn;

    /**
     * The ID to exclude from validation (for updates).
     *
     * @var mixed
     */
    protected $excludeId;

    /**
     * The ID column name.
     *
     * @var string
     */
    protected $idColumn;

    /**
     * Create a new rule instance.
     *
     * @param  string  $table
     * @param  string  $startColumn
     * @param  string  $endColumn
     * @param  mixed  $excludeId
     * @param  string  $idColumn
     * @return void
     */
    public function __construct(
        string $table,
        string $startColumn = 'start_date',
        string $endColumn = 'end_date',
        $excludeId = null,
        string $idColumn = 'id'
    ) {
        $this->table = $table;
        $this->startColumn = $startColumn;
        $this->endColumn = $endColumn;
        $this->excludeId = $excludeId;
        $this->idColumn = $idColumn;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure  $fail
     * @return void
     */
    public function validate($attribute, $value, $fail): void
    {
        $request = request();
        $isStartDate = str_contains($attribute, 'start');

        if ($isStartDate) {
            $startDate = $value;
            $endDate = $request->input($this->getEndFieldName($attribute));
        } else {
            $endDate = $value;
            $startDate = $request->input($this->getStartFieldName($attribute));
        }

        // If we don't have both dates, we can't validate overlaps
        if (! $startDate || ! $endDate) {
            return;
        }

        $query = DB::table($this->table)
            ->where(function ($query) use ($startDate, $endDate) {
                // Case 1: Start date falls within an existing range
                $query->where(function ($q) use ($startDate) {
                    $q->where($this->startColumn, '<=', $startDate)
                      ->where($this->endColumn, '>=', $startDate);
                })
                // Case 2: End date falls within an existing range
                ->orWhere(function ($q) use ($endDate) {
                    $q->where($this->startColumn, '<=', $endDate)
                      ->where($this->endColumn, '>=', $endDate);
                })
                // Case 3: New range encompasses an existing range
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where($this->startColumn, '>=', $startDate)
                      ->where($this->endColumn, '<=', $endDate);
                });
            });

        // Exclude the current record if we're updating
        if ($this->excludeId !== null) {
            $query->where($this->idColumn, '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('validation.date_range_overlap')->translate();
        }
    }

    /**
     * Get the corresponding end date field name from a start date field.
     *
     * @param  string  $startFieldName
     * @return string
     */
    protected function getEndFieldName($startFieldName)
    {
        return str_replace('start', 'end', $startFieldName);
    }

    /**
     * Get the corresponding start date field name from an end date field.
     *
     * @param  string  $endFieldName
     * @return string
     */
    protected function getStartFieldName($endFieldName)
    {
        return str_replace('end', 'start', $endFieldName);
    }

    /**
     * Exclude a specific ID from the validation.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function exclude($id)
    {
        $this->excludeId = $id;

        return $this;
    }
}
