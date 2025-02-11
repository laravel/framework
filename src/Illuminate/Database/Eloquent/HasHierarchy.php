<?php

namespace Illuminate\Database\Eloquent;

trait HasHierarchy
{
    /**
     * Get the root parent ID of the hierarchy for the given model.
     *
     * @param  string  $parentFieldName  The name of the parent ID field. (optional - default: parent_id).
     * @return int|string|null The root parent ID or null if not found.
     */
    public function getHierarchyRootId(string $parentFieldName = 'parent_id'): int|string|null
    {
        $id = $this->id;

        return parent::query()
            ->select($parentFieldName)
            ->whereIn($parentFieldName, function ($query) use ($id, $parentFieldName) {
                $query->select($parentFieldName)
                    ->fromSub(function ($subQuery) use ($id, $parentFieldName) {
                        $subQuery->selectRaw("{$parentFieldName}")
                            ->fromRaw("(WITH RECURSIVE parent_hierarchy AS (
                        SELECT {$parentFieldName}
                        FROM {$this->table}
                        WHERE id = ?

                        UNION ALL

                        SELECT t.{$parentFieldName}
                        FROM {$this->table} t
                        INNER JOIN parent_hierarchy th ON t.id = th.{$parentFieldName}
                    ) SELECT {$parentFieldName} FROM parent_hierarchy) AS recursive_query", [$id]); // Added alias
                    }, 'recursive_query') // Alias for the derived table
                    ->whereNotNull($parentFieldName);
            })
            ->limit(1)
            ->value($parentFieldName);
    }
}
