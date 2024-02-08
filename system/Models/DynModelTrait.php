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


    /*
     * ---------------------------------------------
     * JSON WHERE
     * ---------------------------------------------
     */

    /**
     * @param string|null $key
     * @param mixed $value
     * @return $this
     */
    private function whereJsonSQLMaker(string $key, mixed $value, bool $notIn = false)
    {
        if (!is_array($value))
            $value = [$value];

        $sqlQuery = null;
        $operation = !$notIn ? '=' : '!=';

        foreach ($value as $filter) {
            if ($sqlQuery == null) {
                $sqlQuery = "{$key} {$operation} '{$filter}'";
            } else {
                $sqlQuery .= "OR {$key} {$operation} '{$filter}'";
            }
        }

        $sqlQuery = "({$sqlQuery})";
        return $sqlQuery;
    }

    /**
     * @param string|null $key
     * @param mixed $value
     * @return $this
     */
    private function whereInJson(string $key, mixed $value)
    {
        $this->where($this->whereJsonSQLMaker($key, $value));
    }

    /**
     * @param string|null $key
     * @param mixed $value
     * @return $this
     */
    private function whereNotInJson(string $key, mixed $value)
    {
        $this->where($this->whereJsonSQLMaker($key, $value, true));
    }

    /**
     * @param string|null $key
     * @param mixed $value
     * @return $this
     */
    private function orWhereInJson(string $key, mixed $value)
    {
        $this->orWhere($this->whereJsonSQLMaker($key, $value));
    }

    /**
     * @param string|null $key
     * @param mixed $value
     * @return $this
     */
    private function orWhereNotInJson(string $key, mixed $value)
    {
        $this->orWhere($this->whereJsonSQLMaker($key, $value, true));
    }
}
