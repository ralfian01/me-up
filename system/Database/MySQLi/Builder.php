<?php

namespace MVCME\Database\MySQLi;

use MVCME\Database\DBBuilder;
use MVCME\Database\RawSql;
use Exception;

/**
 * Builder for MySQLi
 */
class Builder extends DBBuilder
{
    /**
     * Identifier escape character
     * @var string
     */
    protected $escapeChar = '`';

    /**
     * Specifies which sql statements support the ignore option.
     * @var array
     */
    protected $supportedIgnoreStatements = [
        'update' => 'IGNORE',
        'insert' => 'IGNORE',
        'delete' => 'IGNORE',
    ];

    /**
     * FROM tables
     * Groups tables in FROM clauses if needed, so there is no confusion about operator precedence. 
     * Note: This is only used (and overridden) by MySQL.
     * @return string
     */
    protected function _fromTables()
    {
        if (!empty($this->QBJoin) && count($this->QBFrom) > 1) {
            return '(' . implode(', ', $this->QBFrom) . ')';
        }

        return implode(', ', $this->QBFrom);
    }

    /**
     * Generates a platform-specific batch update string from the supplied data
     * @return string
     */
    protected function _updateBatch(string $table, array $keys, array $values)
    {
        $sql = $this->QBOptions['sql'] ?? '';

        // if this is the first iteration of batch then we need to build skeleton sql
        if ($sql === '') {
            $constraints = $this->QBOptions['constraints'] ?? [];

            if ($constraints === []) {
                if ($this->db->DBDebug) {
                    throw new Exception("You must specify a constraint to match on for batch updates.");
                }

                return '';
            }

            $updateFields = $this->QBOptions['updateFields'] ??
                $this->updateFields($keys, false, $constraints)->QBOptions['updateFields'] ??
                [];

            $alias = $this->QBOptions['alias'] ?? '`_u`';

            $sql = 'UPDATE ' . $this->compileIgnore('update') . $table . "\n";

            $sql .= "INNER JOIN (\n{:_table_:}";

            $sql .= ') ' . $alias . "\n";

            $sql .= 'ON ' . implode(
                ' AND ',
                array_map(
                    static fn ($key, $value) => (
                        ($value instanceof RawSql && is_string($key))
                        ?
                        $table . '.' . $key . ' = ' . $value
                        : (
                            $value instanceof RawSql
                            ?
                            $value
                            :
                            $table . '.' . $value . ' = ' . $alias . '.' . $value
                        )
                    ),
                    array_keys($constraints),
                    $constraints
                )
            ) . "\n";

            $sql .= "SET\n";

            $sql .= implode(
                ",\n",
                array_map(
                    static fn ($key, $value) => $table . '.' . $key . ($value instanceof RawSql ?
                        ' = ' . $value :
                        ' = ' . $alias . '.' . $value),
                    array_keys($updateFields),
                    $updateFields
                )
            );

            $this->QBOptions['sql'] = $sql;
        }

        if (isset($this->QBOptions['setQueryAsData'])) {
            $data = $this->QBOptions['setQueryAsData'];
        } else {
            $data = implode(
                " UNION ALL\n",
                array_map(
                    static fn ($value) => 'SELECT ' . implode(', ', array_map(
                        static fn ($key, $index) => $index . ' ' . $key,
                        $keys,
                        $value
                    )),
                    $values
                )
            ) . "\n";
        }

        return str_replace('{:_table_:}', $data, $sql);
    }
}
