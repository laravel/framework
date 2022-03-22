<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AllExists implements Rule
{
    use DatabaseRule;

    /**
     * Ignore soft deleted models during the existence check.
     *
     * @param string $deletedAtColumn
     * @return $this
     */
    public function withoutTrashed($deletedAtColumn = 'deleted_at')
    {
        $this->whereNull($deletedAtColumn);

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // if value is empty validation shall pass.
        if (empty($value)) {
            return true;
        }

        $this->validateRuleValue($value);

        $countResult = $this->buildQuery($value)->count($this->column);

        return $countResult == count($value);
    }

    /**
     * build base query for checking existence.
     *
     * @param array $value
     * @return \Illuminate\Database\Query\Builder;
     */
    private function buildQuery($value)
    {
        $query = DB::table($this->table)->whereIn($this->column, $value);

        // add wheres to query.
        $query->where($this->wheres);

        // add closures to query.
        foreach ($this->queryCallbacks() as $closure) {
            $query->where($closure);
        }

        return $query;
    }

    /**
     * validate given values.
     *
     * @param $value
     * @throws \RuntimeException
     */
    private function validateRuleValue($value): void
    {
        if (!is_array($value)) {
            throw new RuntimeException('[AllExistsRule] : validation value must be array.');
        }
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = trans('validation.allExists');

        return $message === 'validation.allExists'
            ? [':attribute are invalid.']
            : $message;
    }
}
