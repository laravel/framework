<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Facades\DB;

trait HasHierarchy
{
    /**
     * Get the root parent ID of the hierarchy for the given model.
     *
     * @param  string  $parentField The name of the parent ID field.
     * @return  int|null The root parent ID or null if not found.
     */
    public function getHierarchyParentId(string $parentField): ?int
    {
        $table = $this->getTable();
        $id = $this->id;

        $result = DB::select("
            WITH RECURSIVE parent_hierarchy AS (
                SELECT $parentField
                FROM $table
                WHERE id = ?

                UNION ALL

                SELECT c.$parentField
                FROM $table c
                INNER JOIN parent_hierarchy ch ON c.id = ch.$parentField
            )
            SELECT $parentField
            FROM parent_hierarchy
            WHERE $parentField IS NOT NULL
            ORDER BY $parentField
            LIMIT 1;
        ", [$id]);

        if (isset($result[0])) {
            return $result[0]->$parentField;
        }

        return null;
    }
}
