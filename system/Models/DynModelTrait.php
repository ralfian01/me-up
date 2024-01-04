<?php

namespace MVCME\Models;

trait DynModelTrait
{
    /*
     * ---------------------------------------------
     * GENERAL TOOLS
     * ---------------------------------------------
     */

    /**
     * Complete column with clear table name
     * @return string
     */
    private function completeTableColumn($column)
    {
        if (count(explode('.', $column)) <= 1)
            return "{$this->table}.{$column}";

        return $column;
    }

    /**
     * Update the usedTableRelation property
     * @return void
     */
    private function updateUsedTableRelation($array)
    {
        foreach ($array as $key => $usage) {
            if (isset($this->usedTableRelations[$key])) {
                $this->usedTableRelations[$key] += $usage;
            } else {
                $this->usedTableRelations[$key] = $usage;
            }
        }
    }
}
