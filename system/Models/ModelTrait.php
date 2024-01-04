<?php

namespace MVCME\Models;

use Exception;
use InvalidArgumentException;

trait ModelTrait
{
    /*
     * ---------------------------------------------
     * GET FROM DATABASE
     * ---------------------------------------------
     */

    /**
     * Fetches the row of database from $this->table with a primary key matching $id.
     * @param bool $singleton Single or multiple results
     * @param array|int|string|null $id One primary key or an array of primary keys
     * @return array|object|null The resulting row of data, or null.
     */
    protected function doFind(bool $singleton, $id = null)
    {
        $builder = $this->builder();

        if ($this->useSoftDeletes) {
            $builder->where($this->table . '.' . $this->deletedField, null);
        }

        if (is_array($id)) {
            $row = $builder->whereIn($this->table . '.' . $this->primaryKey, $id)
                ->get()
                ->getResult($this->returnType);
        } elseif ($singleton) {
            $row = $builder->where(
                $this->table . '.' . $this->primaryKey,
                $id
            )
                ->get()
                ->getFirstRow($this->returnType);
        } else {
            $row = $builder->get()->getResult($this->returnType);
        }

        return $row;
    }

    /**
     * Fetches the column of database from $this->table
     * @param string $columnName Column Name
     * @return array|null The resulting row of data, or null if no data found.
     */
    protected function doFindColumn(string $columnName)
    {
        return $this->select($columnName)->asArray()->find();
    }

    /**
     * Works with the current Query Builder instance to return all results, while optionally limiting them.
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    protected function doFindAll(int $limit = 0, int $offset = 0)
    {
        $builder = $this->builder();

        if ($this->useSoftDeletes) {
            $builder->where($this->table . '.' . $this->deletedField, null);
        }

        return $builder->limit($limit, $offset)
            ->get()
            ->getResult($this->returnType);
    }



    /*
     * ---------------------------------------------
     * INSERT TO DATABASE
     * ---------------------------------------------
     */

    /**
     * Inserts data into the current table
     * @param array $data Data
     * @return bool
     */
    protected function doInsert(array $data)
    {
        $escape = $this->escape;
        $this->escape = [];

        // Require non-empty primaryKey when not using auto-increment feature
        if (!$this->useAutoIncrement && empty($data[$this->primaryKey])) {
            throw new InvalidArgumentException("Primary key cannot be empty");
        }

        $builder = $this->builder();

        // Must use the set() method to ensure to set the correct escape flag
        foreach ($data as $key => $val) {
            $builder->set($key, $val, $escape[$key] ?? null);
        }

        if ($this->allowEmptyInserts && empty($data)) {
            $table = $this->db->protectIdentifiers($this->table, true, null, false);
            if ($this->db->getPlatform() === 'MySQLi') {
                $sql = 'INSERT INTO ' . $table . ' VALUES ()';
            } elseif ($this->db->getPlatform() === 'OCI8') {
                $allFields = $this->db->protectIdentifiers(
                    array_map(
                        static fn ($row) => $row->name,
                        $this->db->getFieldData($this->table)
                    ),
                    false,
                    true
                );

                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $table,
                    implode(',', $allFields),
                    substr(str_repeat(',DEFAULT', count($allFields)), 1)
                );
            } else {
                $sql = 'INSERT INTO ' . $table . ' DEFAULT VALUES';
            }

            $result = $this->db->query($sql);
        } else {
            $result = $builder->insert();
        }

        // If insertion succeeded then save the insert ID
        if ($result) {
            $this->insertID = !$this->useAutoIncrement ? $data[$this->primaryKey] : $this->db->insertID();
        }

        return $result;
    }

    /**
     * Compiles batch insert strings and runs the queries, validating each row prior
     * @param array|null $set An associative array of insert values
     * @param bool|null $escape Whether to escape values
     * @param int $batchSize The size of the batch to run
     * @param bool $testing True means only number of records is returned, false will execute the query
     * @return bool|int Number of rows inserted or FALSE on failure
     */
    protected function doInsertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100)
    {
        if (is_array($set)) {
            foreach ($set as $row) {
                // Require non-empty primaryKey when
                // not using auto-increment feature
                if (!$this->useAutoIncrement && empty($row[$this->primaryKey])) {
                    throw new InvalidArgumentException("Primary key cannot be empty");
                }
            }
        }

        return $this->builder()->insertBatch($set, $escape, $batchSize);
    }

    /**
     * Ensures that only the fields that are allowed to be inserted are in the data array. 
     * Used by insert() and insertBatch() to protect against mass assignment vulnerabilities.
     * @param array $data Data
     * @throws DataException
     */
    protected function doProtectFieldsForInsert(array $data): array
    {
        if (!$this->protectFields) {
            return $data;
        }

        if (empty($this->allowedFields)) {
            throw new InvalidArgumentException("Invalid allowed fields");
        }

        foreach (array_keys($data) as $key) {
            // Do not remove the non-auto-incrementing primary key data.
            if ($this->useAutoIncrement === false && $key === $this->primaryKey) {
                continue;
            }

            if (!in_array($key, $this->allowedFields, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }


    /*
     * ---------------------------------------------
     * UPDATE TO DATABASE
     * ---------------------------------------------
     */

    /**
     * Updates a single record in $this->table
     * @param array|int|string|null $id
     * @param array|null $data
     * @return bool
     */
    protected function doUpdate($id = null, $data = null)
    {
        $escape = $this->escape;
        $this->escape = [];

        $builder = $this->builder();

        if ($id) {
            $builder = $builder->whereIn($this->table . '.' . $this->primaryKey, $id);
        }

        // Must use the set() method to ensure to set the correct escape flag
        foreach ($data as $key => $val) {
            $builder->set($key, $val, $escape[$key] ?? null);
        }

        if ($builder->getCompiledQBWhere() === []) {
            throw new Exception(
                'Updates are not allowed unless they contain a "where" or "like" clause.'
            );
        }

        return $builder->update();
    }

    /**
     * Compiles an update string and runs the query 
     * @param array|null  $set An associative array of update values
     * @param string|null $index The where key
     * @param int $batchSize The size of the batch to run
     * @param bool $returnSQL True means SQL is returned, false will execute the query 
     * @return false|int|string[] Number of rows affected or FALSE on failure, SQL array when testMode 
     */
    protected function doUpdateBatch(?array $set = null, ?string $index = null, int $batchSize = 100)
    {
        return $this->builder()->updateBatch($set, $index, $batchSize);
    }

    /**
     * Ensures that only the fields that are allowed to be updated are
     * in the data array.
     * Used by update() and updateBatch() to protect against mass assignment
     * vulnerabilities.
     * @param array $data Data
     * @return array
     */
    protected function doProtectFieldsForUpdate(array $data)
    {
        if (!$this->protectFields) {
            return $data;
        }

        if (empty($this->allowedFields)) {
            throw new InvalidArgumentException("Invalid allowed fields");
        }

        foreach (array_keys($data) as $key) {
            if (!in_array($key, $this->allowedFields, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }


    /*
     * ---------------------------------------------
     * DELETE FROM DATABASE
     * ---------------------------------------------
     */

    /**
     * Deletes a single record from $this->table where $id matches the table's primaryKey
     * @param array|int|string|null $id The rows primary key(s)
     * @return bool|string
     */
    protected function doDelete($id = null)
    {
        $set = [];
        $builder = $this->builder();

        if ($id) {
            $builder = $builder->whereIn($this->primaryKey, $id);
        }

        return $builder->delete();
    }
}
