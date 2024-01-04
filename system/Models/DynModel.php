<?php

namespace MVCME\Models;

/**
 * Database Model builder. 
 * This class functions to get data from database tables using a prepackaged format
 */
class DynModel extends Model
{
    protected $collectedData;

    public function __construct()
    {
        parent::__construct();

        // Select all column by default
        $this->excludeColumn();
    }

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
     * SETUP SQL STANDARD
     * ---------------------------------------------
     */

    // ### Column aliases

    /**
     * How to use $ignore_at:
     * - Example = [0, 3, 5]
     * 
     * How to use:
     * - Format: [table_column, column_alias]
     * - Example: ["my_table.column", "my_column"]
     * - Example: [["key1" => "my_table.column1", "key2" => "my_table.column2"], "my_column_json"]
     * 
     * Will generate:
     * - "SELECT my_table.column AS my_column"
     * 
     * Set column aliases
     * @var array
     */
    protected $columnAliases = [];

    /**
     * Method for applying standard column aliases
     * @param array $ignore_at Ignore standard table aliases at specific index
     * @return self|object
     */
    private function doColumnAlias(?array $ignore_at = [])
    {

        // Open json recursively
        $processJsonColumn = function ($json) use (&$processJsonColumn) {
            $jsonColumn = [];

            foreach ($json as $jsKey => $jsItem) {
                if (is_array($jsItem)) {

                    // $jsonColumn[] = $processJsonColumn($jsItem);
                    $jsonColumn[] = "'{$jsKey}', JSON_OBJECT(" . implode(', ', $processJsonColumn($jsItem)) . ")";
                    continue;
                }

                // Complete rule with clear table name
                $jsonColumn[] = "'{$jsKey}', " . $this->completeTableColumn($jsItem);
            }

            return $jsonColumn;
        };

        foreach ($this->columnAliases as $clKey => $clVal) {
            // Check whether filtering should be ignored or not
            if (in_array($clKey, $ignore_at)) continue;

            // Check whether the column is the selected column when returning data or not
            if (!in_array($clVal[1], $this->selectColumnData)) continue;

            // Check whether the selected column is returned in json format or not
            if (is_array($clVal[0])) {
                $clVal[0] = $processJsonColumn($clVal[0]);

                // Return column in json format
                $clVal[0] = implode(', ', $clVal[0]);
                $clVal[0] = "JSON_OBJECT({$clVal[0]})";
            } else {

                // Complete rule with clear table name
                $clVal[0] = $this->completeTableColumn($clVal[0]);
            }

            $this->select("{$clVal[0]} AS {$clVal[1]}");
        }

        // Update table relation from column alias
        $this->updateUsedTableRelation($this->columnAliasTableRelation());
        $this->doTableRelation();

        return $this;
    }

    /**
     * How to use $ignore_at:
     * - Example = [0, 3, 5]
     * 
     * Method for applying column alias using standard column aliases
     * @param array $ignore_at Ignore standard table aliases at specific index
     * @return self|object
     */
    protected function setStdColumnAlias(?array $ignore_at = [])
    {
        $this->doColumnAlias($ignore_at ?? []);
        return $this;
    }

    /**
     * Method for override standard column aliases
     * @return self|object
     */
    protected function setColumnAlias(array $columnAliases)
    {
        $this->columnAliases = $columnAliases;
        return $this->doColumnAlias();
    }

    /**
     * Tools: Method for checking whether a table is used in a table relationship based on the column aliases used
     * @return array
     */
    private function columnAliasTableRelation()
    {
        $tables = [];

        $appendTable = function ($item, $alias) use (&$tables) {
            $tblColumn = explode('.', $item);

            // Complete column name
            if (count($tblColumn) <= 1) array_unshift($tblColumn, $this->table);

            if ($tblColumn[0] != $this->table) {
                // Initiate table
                if (!isset($tables[$tblColumn[0]]))
                    $tables[$tblColumn[0]] = 0;

                if (in_array($alias, $this->selectColumnData))
                    $tables[$tblColumn[0]] += 1;
            }

            return $tables;
        };

        // Open json
        $openJson = function ($json, $alias) use (&$openJson, $appendTable) {
            foreach ($json as $jsKey => $jsItem) {
                if (is_array($jsItem)) {

                    $openJson($jsItem, $alias);
                    continue;
                }

                $appendTable($jsItem, $alias);
            }
        };

        // Collect table list
        foreach ($this->columnAliases as $alias) {
            if (is_array($alias[0])) {
                $openJson($alias[0], $alias[1]);
            } else {
                $appendTable($alias[0], $alias[1]);
            }
        }

        return $tables;
    }


    // ### Table relation

    /**
     * How to use:
     * - Format: [table_to_join, join_operation, join_method]
     * - Example: ["my_table2", "my_table2.id = my_table1.id", "LEFT"]
     * 
     * Will generate:
     * - "LEFT JOIN my_table2 ON my_table2.id = my_table1.id"
     * 
     * Set table relations
     * @var array
     */
    protected $tableRelations = [];

    /**
     * List of tables used for table relationships
     */
    private $usedTableRelations = [];

    /**
     * List of registered table relationships
     */
    private $registeredTableRelations = [];

    /**
     * How to use $ignore_at:
     * - Example = [0, 3, 5]
     * 
     * Method for applying standard table relation
     * @param array $ignore_at Ignore standard table relation at specific index
     * @return self|object
     */
    private function doTableRelation(?array $ignore_at = [])
    {
        // Check whether the selected column requires a table relation/join or not
        $ignore_relation = [];
        foreach ($this->usedTableRelations as $relKey => $relNum) {
            if ($relNum == 0) $ignore_relation[] = $relKey;
        }

        foreach ($this->tableRelations as $tbKey => $tbVal) {
            // Check whether filtering should be ignored or not
            if (in_array($tbKey, $ignore_at)) continue;

            // If the column is selected, it does not require a relation/join table
            if (in_array($tbVal[0], $ignore_relation)) continue;

            // Skip if table relation already registereds
            if (in_array($tbVal[0], $this->registeredTableRelations)) continue;

            $this->join($tbVal[0], $tbVal[1], $tbVal[2]);

            // Register table relation
            array_push($this->registeredTableRelations, $tbVal[0]);
        }

        return $this;
    }

    /**
     * How to use $ignore_at:
     * - Example = [0, 3, 5]
     * 
     * Method for applying table relation using standard table relations
     * @param array $ignore_at Ignore standard table relation at specific index
     * @return self|object
     */
    protected function setStdTableRelation(?array $ignore_at = [])
    {
        $this->doTableRelation($ignore_at ?? []);
        return $this;
    }

    /**
     * Method for applying table relation using standard table relations
     * @return self|object
     */
    protected function setTableRelation(array $tableRelations)
    {
        $this->tableRelations = $tableRelations;
        return $this->doTableRelation();
    }


    // ### Filter Column aliases

    /**
     * Filter operations:
     * - where
     * - whereIn
     * - whereNot
     * - whereNotIn
     * - like
     * - notLike
     * - orWhere
     * - orWhereIn
     * - orWhereNotIn
     * - orLike
     * - orNotLike
     * - whereJson
     * - whereInJson
     * - whereNotJson
     * - whereNotInJson
     * - likeJson
     * - notLikeJson
     * - orWhereJson
     * - orWhereInJson
     * - orWhereNotInJson
     * - orLikeJson
     * - orNotLikeJson
     * 
     * How to use:
     * - Format: filter_key => [table_column, filter_operation]
     * - Example: "column_id" => ["my_table.column_id", "whereIn"]
     * - Example: "column_id" => ["my_table.column_1 && my_table.column_2", "where"]
     * - Example: "column_id" => ["my_table.column.$.json_key", "whereJson"]
     * 
     * Set column aliases in filter
     * @var array
     */
    protected $filterData = [];

    /**
     * How to use $ignore_at:
     * - Example = ['acc_id', 'username']
     * 
     * Method for applying standard filters and column
     * @param array $ignore_at Ignore standard table relation at specific index
     * @return self|object
     */
    private function doFilter(array $filter, ?array $ignore_at = [])
    {

        foreach ($filter as $ftKey => $ftVal) {
            // Make sure array key is available
            if (isset($this->filterData[$ftKey])) {

                // Check whether filtering should be ignored or not
                if (in_array($ftKey, $ignore_at)) continue;

                // Apply filter column rules
                $this->filterColumn($ftKey, $ftVal);
            }
        }

        // Update table relation from column filter data
        $this->updateUsedTableRelation($this->filterDataTableRelation($filter));
        $this->doTableRelation();

        return $this;
    }

    /**
     * How to use $ignore_at:
     * - Example = ['acc_id', 'username']
     * 
     * Method for applying filter using standard filters and column
     * @param array $ignore_at Ignore standard table relation at specific index
     * @return self|object
     */
    protected function setStdFilter(array $filter, ?array $ignore_at = [])
    {
        $this->doFilter($filter, $ignore_at ?? []);
        return $this;
    }

    /**
     * Filter operations:
     * - where
     * - whereIn
     * - whereNot
     * - whereNotIn
     * - like
     * - notLike
     * - orWhere
     * - orWhereIn
     * - orWhereNotIn
     * - orLike
     * - orNotLike
     * - whereJson
     * - whereInJson
     * - whereNotJson
     * - whereNotInJson
     * - likeJson
     * - notLikeJson
     * - orWhereJson
     * - orWhereInJson
     * - orWhereNotInJson
     * - orLikeJson
     * - orNotLikeJson
     * 
     * How to use:
     * - Format: filter_key => [table_column, filter_operation]
     * - Example: "column_id" => ["my_table.column_id", "whereIn"]
     * - Example: "column_id" => ["my_table.column_1 && my_table.column_2", "where"]
     * - Example: "column_id" => ["my_table.column.$.json_key", "whereJson"]
     * 
     * Method for override standard filters and column
     * @return self|object
     */
    protected function setFilter(array $filterData, array $filter)
    {
        $this->filterData = $filterData;
        return $this->doFilter($filter);
    }

    /**
     * Tools: Method to apply a filter to a column by checking whether the filter is multicolumn or single
     * @return self|object
     */
    private function filterColumn($filterKey, $filterValue)
    {

        // If the column in question is in json format
        $jsonColumn = function (&$filterData) {
            $filterData[1] = str_ireplace('JSON', '', $filterData[1]);

            $blockColumn = explode('.$.', $filterData[0]);
            $blockColumn[0] = $this->completeTableColumn($blockColumn[0]);

            $filterData[0] = "JSON_EXTRACT({$blockColumn[0]}, '$.{$blockColumn[1]}')";
            return $filterData;
        };


        // Check whether the filtration is a combination of several columns
        if (
            strpos($this->filterData[$filterKey][0], "/\b(||)\b/i") >= 1
            || strpos($this->filterData[$filterKey][0], "/\b(&&)\b/i") >= 1
        ) {

            // Filter data multiple columns

            // If the column in question is in json format
            if (strpos(strtoupper($this->filterData[$filterKey][1]), "JSON") >= 1)
                $jsonColumn($this->filterData[$filterKey]);

            // Function to add value to column
            $addValue = function ($match) use ($filterKey, $filterValue) {

                // Complete rule with clear table name
                $match[0] = $this->completeTableColumn($match[0]);

                // Set value to array
                if (in_array($this->filterData[$filterKey][0], ['whereIn', 'whereNotIn', 'orWhereIn', 'orWhereNotIn'])) {

                    $filterValue = [$filterValue];
                    $filterValue = implode("','", $filterValue);
                    return "{$match[0]} IN ('{$filterValue}')";
                }
                return "{$match[0]} = '{$filterValue}'";
            };

            $this->filterData[$filterKey][0] = preg_replace_callback('/([a-zA-Z_]\w*)/', fn ($cb) => $addValue($cb), $this->filterData[$filterKey][0]);
            $this->filterData[$filterKey][0] = str_replace('||', 'OR', $this->filterData[$filterKey][0]);
            $this->filterData[$filterKey][0] = str_replace('&&', 'AND', $this->filterData[$filterKey][0]);

            $this->{$this->filterData[$filterKey][1]}(
                $this->filterData[$filterKey][0]
            );
        } else {

            // Filter data single column

            // If the column in question is in json format
            if (strpos(strtoupper($this->filterData[$filterKey][1]), "JSON") >= 1)
                $jsonColumn($this->filterData[$filterKey]);

            // Complete rule with clear table name
            $this->filterData[$filterKey][0] = $this->completeTableColumn($this->filterData[$filterKey][0]);

            // Set value to array
            if (in_array($this->filterData[$filterKey][1], ['whereIn', 'whereNotIn', 'orWhereIn', 'orWhereNotIn']))
                if (!is_array($filterValue)) $filterValue = [$filterValue];

            $this->{$this->filterData[$filterKey][1]}(
                $this->filterData[$filterKey][0],
                $filterValue
            );
        }

        return $this;
    }


    /**
     * Tools: Method for checking whether a table is used in a table relationship based on the column filter data used
     * @return array
     */
    private function filterDataTableRelation(array $filter)
    {
        $tables = [];

        $appendTable = function ($item) use (&$tables) {
            $tblColumn = explode('.', $item);

            // Complete column name
            if (count($tblColumn) <= 1) array_unshift($tblColumn, $this->table);

            if ($tblColumn[0] != $this->table) {

                // Initiate table
                if (!isset($tables[$tblColumn[0]]))
                    $tables[$tblColumn[0]] = 0;

                $tables[$tblColumn[0]] += 1;
            }

            return $tables;
        };

        // Collect table list
        foreach ($filter as $ftKey => $ftVal) {
            if (!isset($this->filterData[$ftKey]))
                continue;

            if (
                strpos($this->filterData[$ftKey][0], "/\b(||)\b/i") >= 1
                || strpos($this->filterData[$ftKey][0], "/\b(&&)\b/i") >= 1
            ) {
                // code...
            } else {
                $appendTable($this->filterData[$ftKey][0]);
            }
        }

        // // Open json
        // $openJson = function ($json, $alias) use (&$openJson, $appendTable) {
        //     foreach ($json as $jsKey => $jsItem) {
        //         if (is_array($jsItem)) {

        //             $openJson($jsItem, $alias);
        //             continue;
        //         }

        //         $appendTable($jsItem, $alias);
        //     }
        // };

        return $tables;
    }


    // ### Data limiter

    /**
     * Method for applying standard data limiter
     * @param null|int|array $limit [offset, limit]
     * @return self|object
     */
    protected function setLimiter($limit = null)
    {
        if ($limit != null) {
            if (!is_array($limit)) $limit = [$limit];
            if (count($limit) < 2) $limit[1] = 0;

            $this->limit($limit[0], $limit[1]);
        }

        return $this;
    }


    /*
     * ---------------------------------------------
     * BEFORE RETURN DATA 
     * ---------------------------------------------
     */

    // ### Select return column

    protected $selectColumnData = [];

    /**
     * Method for selecting which columns to select when returning data
     * 
     * !! Disclaimer !! The selected columns are only columns that have been registered in the $columnAlias
     * 
     * This method will affect the final SQL results such as joined tables.
     * If the selected column does not require a table join then SQL will not perform a table join
     * 
     * @return self|object
     */
    public function selectColumn(?array $column = null)
    {
        if ($column == null) return $this;

        $this->selectColumnData = $column;
        return $this;
    }

    /**
     * Method for selecting which columns to exclude when returning data
     * 
     * !! Disclaimer !! The selected columns are only columns that have been registered in the $columnAlias
     * 
     * This method will affect the final SQL results such as joined tables.
     * If the selected column does not require a table join then SQL will not perform a table join
     * 
     * @return self|object
     */
    public function excludeColumn(?array $column = [])
    {
        if ($column == null) $column = [];

        // Collect all column
        foreach ($this->columnAliases as $val) {
            $this->selectColumnData[] = $val[1];
        }

        // Remove column from collected column
        foreach ($this->selectColumnData as $key => $val) {
            if (in_array($val, $column))
                unset($this->selectColumnData[$key]);
        }

        return $this;
    }


    /*
     * ---------------------------------------------
     * RETURN DATA 
     * ---------------------------------------------
     */

    /**
     * Method for getting data length based on primary key
     * @return int|string
     */
    public function length()
    {
        $result = $this
            ->select("COUNT({$this->table}.{$this->primaryKey}) as length")
            ->first();

        if ($result == null) return 0;
        return $result['length'];
    }

    /**
     * Method for getting the results of adding row IDs
     * @return int|string
     */
    public function sumOfID()
    {
        $result = $this
            ->select("SUM({$this->table}.{$this->primaryKey}) as summmary")
            ->first();

        if ($result == null) return 0;
        return $result['summmary'];
    }
}
