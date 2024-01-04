<?php

namespace MVCME\Models;

use MVCME\Database\DBBuilder;
use MVCME\Database\DBConnection;
use MVCME\Database\DBConnectionInterface;
use MVCME\Database\DBResult;
use AppConfig\Database;
use BadMethodCallException;
use InvalidArgumentException;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

/**
 * The Model class extends BaseModel and provides additional
 * convenient features that makes working with a SQL database
 *
 * @property DBConnection $db
 *
 * @method $this groupBy($by, ?bool $escape = null)
 * @method $this groupEnd()
 * @method $this groupStart()
 * @method $this join(string $table, string $cond, string $type = '', ?bool $escape = null)
 * @method $this like($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this limit(?int $value = null, ?int $offset = 0)
 * @method $this notGroupStart()
 * @method $this notHavingGroupStart()
 * @method $this notHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this notLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this offset(int $offset)
 * @method $this orderBy(string $orderBy, string $direction = '', ?bool $escape = null)
 * @method $this orGroupStart()
 * @method $this orHaving($key, $value = null, ?bool $escape = null)
 * @method $this orHavingGroupStart()
 * @method $this orHavingIn(?string $key = null, $values = null, ?bool $escape = null)
 * @method $this orHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this orHavingNotIn(?string $key = null, $values = null, ?bool $escape = null)
 * @method $this orLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this orNotGroupStart()
 * @method $this orNotHavingGroupStart()
 * @method $this orNotHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this orNotLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this orWhere($key, $value = null, ?bool $escape = null)
 * @method $this orWhereIn(?string $key = null, $values = null, ?bool $escape = null)
 * @method $this orWhereNotIn(?string $key = null, $values = null, ?bool $escape = null)
 * @method $this select($select = '*', ?bool $escape = null)
 * @method $this where($key, $value = null, ?bool $escape = null)
 * @method $this whereIn(?string $key = null, $values = null, ?bool $escape = null)
 * @method $this whereNotIn(?string $key = null, $values = null, ?bool $escape = null)
 */
class Model
{
    use ModelTrait;

    /**
     * Name of database table
     * @var string
     */
    protected $table;

    /**
     * The table's primary key.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Whether primary key uses auto increment.
     */
    protected $useAutoIncrement = true;

    /**
     * Query Builder object
     * @var DBBuilder|null
     */
    protected $builder;

    /**
     * Holds information passed in via 'set' so that we can capture it (not the builder) and ensure it gets validated first.
     * @var array
     */
    protected $tempData = [];

    /**
     * Escape array that maps usage of escape flag for every parameter
     * @var array
     */
    protected $escape = [];


    /**
     * Last insert ID 
     * @var int|string
     */
    protected $insertID = 0;

    /**
     * The Database connection group that should be instantiated. 
     * @var string|null 
     */
    protected $DBGroup;

    /**
     * The format that the results should be returned as.
     * @var string
     */
    protected $returnType = 'array';

    /**
     * If this model should use "softDeletes" and simply set a date when rows are deleted, or do hard deletes. 
     * @var bool
     */
    protected $useSoftDeletes = false;

    /**
     * An array of field names that are allowed to be set by the user in inserts/updates.
     * @var array
     */
    protected $allowedFields = [];

    /**
     * Used by withDeleted to override the model's softDelete setting.
     * @var bool
     */
    protected $tempUseSoftDeletes;

    /**
     * Used by asArray and asObject to provide temporary overrides of model default.
     * @var string
     */
    protected $tempReturnType;

    /**
     * Whether we should limit fields in inserts and updates to those available in $allowedFields or not.
     * @var bool
     */
    protected $protectFields = true;

    /**
     * Database Connection
     * @var DBConnection
     */
    protected $db;

    /**
     * Whether to allow inserting empty data.
     */
    protected bool $allowEmptyInserts = false;

    /**
     * Builder method names that should not be used in the Model. 
     * @var string[] method name
     */
    private array $builderMethodsNotAvailable = [
        'getCompiledInsert',
        'getCompiledSelect',
        'getCompiledUpdate',
    ];


    public function __construct(?DBConnectionInterface $db = null)
    {
        $this->db = $db ?? Database::connect($this->DBGroup);
    }

    /**
     * Provides/instantiates the builder/db connection and model's table/primary key names and return type.
     * @param string $name Name
     * @return array|DBBuilder|bool|float|int|object|string|null
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return $this->builder()->{$name} ?? null;
    }

    /**
     * Checks for the existence of properties across this model, builder, and db connection.
     * @param string $name Name
     */
    public function __isset(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        return isset($this->builder()->{$name});
    }

    /**
     * Provides direct access to method in the builder (if available) and the database connection.
     * @return $this|array|DBBuilder|bool|float|int|object|string|null
     */
    public function __call(string $name, array $params)
    {
        $builder = $this->builder();
        $result = null;

        if (method_exists($this->db, $name)) {
            $result = $this->db->{$name}(...$params);
        } elseif (method_exists($builder, $name)) {
            $this->checkBuilderMethod($name);

            $result = $builder->{$name}(...$params);
        } else {
            throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name);
        }

        if ($result instanceof DBBuilder) {
            return $this;
        }

        return $result;
    }

    /**
     * Provides a shared instance of the Query Builder.
     * @return DBBuilder
     */
    public function builder(?string $table = null)
    {
        // Check for an existing Builder
        if ($this->builder instanceof DBBuilder) {
            // Make sure the requested table matches the builder
            if ($table && $this->builder->getTable() !== $table) {
                return $this->db->table($table);
            }

            return $this->builder;
        }

        // Force a primary key to exist
        if (empty($this->primaryKey)) {
            throw new InvalidArgumentException("Primary key cannot be empty");
        }

        $table = empty($table) ? $this->table : $table;

        // Ensure we have a good db connection
        if (!$this->db instanceof DBConnection) {
            $this->db = Database::connect($this->DBGroup);
        }

        $builder = $this->db->table($table);

        // Only consider it "shared" if the table is correct
        if ($table === $this->table) {
            $this->builder = $builder;
        }

        return $builder;
    }

    /**
     * Checks the Builder method name that should not be used in the Model.
     * @return void
     */
    private function checkBuilderMethod(string $name)
    {
        if (in_array($name, $this->builderMethodsNotAvailable, true)) {
            throw new InvalidArgumentException("Method is not available");
        }
    }

    /**
     * Sets $allowEmptyInserts.
     * @return $this
     */
    public function allowEmptyInserts(bool $value = true)
    {
        $this->allowEmptyInserts = $value;

        return $this;
    }

    /**
     * Transform data to array.
     * @param array|object|null $data Data
     * @param string $type Type of data (insert|update)
     */
    protected function transformDataToArray($data, string $type): array
    {
        if (!in_array($type, ['insert', 'update'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid type "%s" used upon transforming data to array.', $type));
        }

        if (!$this->allowEmptyInserts && empty($data)) {
            throw new Exception("No data inserted");
        }

        // If $data is using a custom class with public or protected
        // properties representing the collection elements, we need to grab
        // them as an array.
        if (is_object($data) && !$data instanceof stdClass) {
            // If it validates with entire rules, all fields are needed.
            $onlyChanged = ($this->skipValidation === false && $this->cleanValidationRules === false)
                ? false : ($type === 'update');

            $data = $this->objectToArray($data, $onlyChanged, true);
        }

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data)) {
            $data = (array) $data;
        }

        // If it's still empty here, means $data is no change or is empty object
        if (!$this->allowEmptyInserts && empty($data)) {
            throw new Exception("No data inserted");
        }

        return $data;
    }

    /**
     * Takes a class and returns an array of its public and protected
     * properties as an array suitable for use in creates and updates. 
     * @param object|string $data Data
     * @param bool $onlyChanged Only Changed Property
     * @param bool $recursive If true, inner entities will be cast as array as well
     * @return array Array
     */
    protected function objectToArray($data, bool $onlyChanged = true, bool $recursive = false): array
    {
        $properties = $this->objectToRawArray($data, $onlyChanged, $recursive);

        assert(is_array($properties));

        // Convert any Time instances to appropriate $dateFormat
        if ($properties !== []) {
            $properties = array_map(
                function ($value) {
                    return $value;
                },
                $properties
            );
        }

        return $properties;
    }

    /**
     * Takes a class and returns an array of its public and protected properties as an array with raw values.
     * @param object|string $data Data
     * @param bool $onlyChanged Only Changed Property
     * @param bool $recursive If true, inner entities will be casted as array as well
     * @return array|null Array
     */
    protected function objectToRawArray($data, bool $onlyChanged = true, bool $recursive = false): ?array
    {
        if (method_exists($data, 'toRawArray')) {
            $properties = $data->toRawArray($onlyChanged, $recursive);
        } else {
            $mirror = new ReflectionClass($data);
            $props = $mirror->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

            $properties = [];

            // Loop over each property,
            // saving the name/value in a new array we can return.
            foreach ($props as $prop) {
                // Must make protected values accessible.
                $prop->setAccessible(true);
                $properties[$prop->getName()] = $prop->getValue($data);
            }
        }

        return $properties;
    }


    /*
     * ---------------------------------------------
     * GET FROM DATABASE
     * ---------------------------------------------
     */

    /**
     * Fetches the row of database.
     * @param array|int|string|null $id One primary key or an array of primary keys 
     * @return array|object|null The resulting row of data, or null.
     */
    public function find($id = null)
    {
        $singleton = is_numeric($id) || is_string($id);

        $returnData = [
            'id' => $id,
            'data' => $this->doFind($singleton, $id),
            'method' => 'find',
            'singleton' => $singleton,
        ];

        return $returnData['data'];
    }

    /**
     * Fetches the column of database.
     * @param string $columnName Column Name
     * @return array|null The resulting row of data, or null if no data found.
     */
    public function findColumn(string $columnName)
    {
        if (strpos($columnName, ',') !== false) {
            throw new InvalidArgumentException("You can only choose one column");
        }

        $resultSet = $this->doFindColumn($columnName);

        return $resultSet ? array_column($resultSet, $columnName) : null;
    }

    /**
     * Fetches all results, while optionally limiting them.
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function findAll(int $limit = 0, int $offset = 0)
    {
        $returnData = [
            'data' => $this->doFindAll($limit, $offset),
            'limit' => $limit,
            'offset' => $offset,
            'method' => 'findAll',
            'singleton' => false,
        ];

        return $returnData['data'];
    }


    /*
     * ---------------------------------------------
     * INSERT TO DATABASE
     * ---------------------------------------------
     */

    /**
     * Inserts data into the database. If an object is provided, it will attempt to convert it to an array.
     * @param array|object|null $data Data
     * @param bool $returnID Whether insert ID should be returned or not.
     * @return bool|int|string insert ID or true on success. false on failure
     */
    public function insert($data = null, bool $returnID = true)
    {
        $this->insertID = 0;

        $data = $this->transformDataToArray($data, 'insert');

        // Must be called first, so we don't strip out created_at values.
        $data = $this->doProtectFieldsForInsert($data);

        // doProtectFieldsForUpdate() can further remove elements from
        // $data so we need to check for empty dataset again
        if (!$this->allowEmptyInserts && empty($data)) {
            throw new Exception("Cannot insert empty field");
        }

        $returnData = ['data' => $data];

        $result = $this->doInsert($returnData['data']);

        $returnData = [
            'id' => $this->insertID,
            'data' => $returnData['data'],
            'result' => $result,
        ];

        // If insertion failed, get out of here
        if (!$result) {
            return $result;
        }

        // otherwise return the insertID, if requested.
        return $returnID ? $this->insertID : $result;
    }

    /**
     * Compiles batch insert runs the queries, validating each row prior.
     * @param array|null $set an associative array of insert values
     * @param bool|null $escape Whether to escape values
     * @param int $batchSize The size of the batch to run
     * @return bool|int Number of rows inserted or FALSE on failure
     */
    public function insertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100)
    {
        if (is_array($set)) {
            foreach ($set as &$row) {
                // If $data is using a custom class with public or protected
                // properties representing the collection elements, we need to grab
                // them as an array.
                if (is_object($row) && !$row instanceof stdClass) {
                    $row = $this->objectToArray($row, false, true);
                }

                // If it's still a stdClass, go ahead and convert to
                // an array so doProtectFields and other model methods
                // don't have to do special checks.
                if (is_object($row)) {
                    $row = (array) $row;
                }

                $row = $this->doProtectFieldsForInsert($row);
            }
        }

        $returnData = ['data' => $set];

        $result = $this->doInsertBatch($returnData['data'], $escape, $batchSize);

        $returnData = [
            'data' => $returnData['data'],
            'result' => $result,
        ];

        return $result;
    }

    /**
     * Returns last insert ID or 0. 
     * @return int|string
     */
    public function getInsertID()
    {
        return is_numeric($this->insertID) ? (int) $this->insertID : $this->insertID;
    }


    /*
     * ---------------------------------------------
     * UPDATE TO DATABASE
     * ---------------------------------------------
     */

    /**
     * Updates a single record in the database. If an object is provided.
     * @param array|int|string|null $id
     * @param array|object|null $data
     * @return bool
     */
    public function update($id = null, $data = null): bool
    {
        if (is_bool($id)) {
            throw new InvalidArgumentException('update(): argument #1 ($id) should not be boolean.');
        }

        if (is_numeric($id) || is_string($id)) {
            $id = [$id];
        }

        $data = $this->transformDataToArray($data, 'update');


        // Must be called first, so we don't
        // strip out updated_at values.
        $data = $this->doProtectFieldsForUpdate($data);

        // doProtectFields() can further remove elements from
        // $data, so we need to check for empty dataset again
        if (empty($data)) {
            throw new Exception("Cannot update empty field");
        }

        $returnData = [
            'id' => $id,
            'data' => $data,
        ];

        $result = $this->doUpdate(...$returnData);

        $returnData = [
            'id' => $id,
            'data' => $returnData['data'],
            'result' => $result,
        ];

        return $returnData['result'];
    }

    /**
     * Compiles an update and runs the query.
     * @param array|null $set An associative array of update values
     * @param string|null $index The where key
     * @param int $batchSize The size of the batch to run
     * @return false|int|string[] Number of rows affected or FALSE on failure, SQL array when testMode
     */
    public function updateBatch(?array $set = null, ?string $index = null, int $batchSize = 100)
    {
        if (is_array($set)) {
            foreach ($set as &$row) {
                // If $data is using a custom class with public or protected
                // properties representing the collection elements, we need to grab
                // them as an array.
                if (is_object($row) && !$row instanceof stdClass) {
                    // For updates the index field is needed even if it is not changed.
                    // So set $onlyChanged to false.
                    $row = $this->objectToArray($row, false, true);
                }

                // If it's still a stdClass, go ahead and convert to
                // an array so doProtectFields and other model methods
                // don't have to do special checks.
                if (is_object($row)) {
                    $row = (array) $row;
                }

                // Save updateIndex for later
                $updateIndex = $row[$index] ?? null;

                if ($updateIndex === null) {
                    throw new InvalidArgumentException(
                        'The index ("' . $index . '") for updateBatch() is missing in the data: '
                            . json_encode($row)
                    );
                }

                // Must be called first so we don't
                // strip out updated_at values.
                $row = $this->doProtectFieldsForUpdate($row);

                // Restore updateIndex value in case it was wiped out
                if ($updateIndex !== null) {
                    $row[$index] = $updateIndex;
                }
            }
        }

        $returnData = ['data' => $set];

        $result = $this->doUpdateBatch($returnData['data'], $index, $batchSize);

        $returnData = [
            'data' => $returnData['data'],
            'result' => $result,
        ];

        return $result;
    }


    /*
     * ---------------------------------------------
     * DELETE FROM DATABASE
     * ---------------------------------------------
     */

    /**
     * Deletes a single record from the database where $id matches.
     * @param array|int|string|null $id The rows primary key(s)
     * @return BaseResult|bool
     */
    public function delete($id = null)
    {
        if (is_bool($id)) {
            throw new InvalidArgumentException('delete(): argument #1 ($id) should not be boolean.');
        }

        if ($id && (is_numeric($id) || is_string($id))) {
            $id = [$id];
        }

        $returnData = [
            'id' => $id
        ];

        $result = $this->doDelete(...$returnData);

        $returnData = [
            'id' => $id,
            'data' => null,
            'result' => $result,
        ];

        return $returnData['result'];
    }

    // /**
    //  * Grabs the last error(s) that occurred from the Database connection
    //  * @return array<string,string>
    //  */
    // protected function doErrors()
    // {
    //     // $error is always ['code' => string|int, 'message' => string]
    //     $error = $this->db->error();

    //     if ((int) $error['code'] === 0) {
    //         return [];
    //     }

    //     return [get_class($this->db) => $error['message']];
    // }

    // /**
    //  * Returns the id value for the data array or object
    //  * @param array|object $data Data
    //  * @return array|int|string|null
    //  * @deprecated Use getIdValue() instead. Will be removed in version 5.0.
    //  */
    // protected function idValue($data)
    // {
    //     return $this->getIdValue($data);
    // }

    // /**
    //  * Returns the id value for the data array or object
    //  * @param array|object $data Data
    //  * @return array|int|string|null
    //  */
    // public function getIdValue($data)
    // {
    //     if (is_object($data) && isset($data->{$this->primaryKey})) {
    //         return $data->{$this->primaryKey};
    //     }

    //     if (is_array($data) && !empty($data[$this->primaryKey])) {
    //         return $data[$this->primaryKey];
    //     }

    //     return null;
    // }

    // /**
    //  * Loops over records in batches, allowing you to operate on them.
    //  * Works with $this->builder to get the Compiled select to determine the rows to operate on.
    //  * @return void
    //  * @throws DataException
    //  */
    // public function chunk(int $size, Closure $userFunc)
    // {
    //     $total = $this->builder()->countAllResults(false);
    //     $offset = 0;

    //     while ($offset <= $total) {
    //         $builder = clone $this->builder();
    //         $rows = $builder->get($size, $offset);

    //         if (!$rows) {
    //             throw new InvalidArgumentException("Data \"chunk\" cannot be empty");
    //         }

    //         $rows = $rows->getResult($this->returnType);

    //         $offset += $size;

    //         if (empty($rows)) {
    //             continue;
    //         }

    //         foreach ($rows as $row) {
    //             if ($userFunc($row) === false) {
    //                 return;
    //             }
    //         }
    //     }
    // }

    // /**
    //  * Override countAllResults to account for soft deleted accounts.
    //  * @return int|string
    //  */
    // public function countAllResults(bool $reset = true, bool $test = false)
    // {
    //     if ($this->useSoftDeletes) {
    //         $this->builder()->where($this->table . '.' . $this->deletedField, null);
    //     }

    //     // When $reset === false, the $tempUseSoftDeletes will be
    //     // dependent on $useSoftDeletes value because we don't
    //     // want to add the same "where" condition for the second time
    //     $this->useSoftDeletes = $reset
    //         ? $this->useSoftDeletes
    //         : ($this->useSoftDeletes ? false : $this->useSoftDeletes);

    //     return $this->builder()->testMode($test)->countAllResults($reset);
    // }

    // /**
    //  * Captures the builder's set() method so that we can validate the data here.
    //  * This allows it to be used with any of the other builder methods and still get validated data, like replace.
    //  * @param array|object|string $key Field name, or an array of field/value pairs
    //  * @param bool|float|int|object|string|null $value Field value, if $key is a single field
    //  * @param bool|null $escape Whether to escape values
    //  * @return $this
    //  */
    // public function set($key, $value = '', ?bool $escape = null)
    // {
    //     $data = is_array($key) ? $key : [$key => $value];

    //     foreach (array_keys($data) as $k) {
    //         $this->tempData['escape'][$k] = $escape;
    //     }

    //     $this->tempData['data'] = array_merge($this->tempData['data'] ?? [], $data);

    //     return $this;
    // }

    // /**
    //  * This method is called on save to determine if entry have to be updated If this method return false insert operation
    //  * will be executed
    //  * @param array|object $data Data
    //  */
    // protected function shouldUpdate($data): bool
    // {
    //     if (parent::shouldUpdate($data) === false) {
    //         return false;
    //     }

    //     if ($this->useAutoIncrement === true) {
    //         return true;
    //     }

    //     // When useAutoIncrement feature is disabled, check
    //     // in the database if given record already exists
    //     return $this->where($this->primaryKey, $this->getIdValue($data))->countAllResults() === 1;
    // }

    // /**
    //  * Updates a single record in the database. If an object is provided, it will attempt to convert it into an array.
    //  * @param array|int|string|null $id
    //  * @param array|object|null $data
    //  * @throws ReflectionException
    //  */
    // public function update($id = null, $data = null): bool
    // {
    //     if (!empty($this->tempData['data'])) {
    //         if (empty($data)) {
    //             $data = $this->tempData['data'];
    //         } else {
    //             $data = $this->transformDataToArray($data, 'update');
    //             $data = array_merge($this->tempData['data'], $data);
    //         }
    //     }

    //     $this->escape = $this->tempData['escape'] ?? [];
    //     $this->tempData = [];

    //     return parent::update($id, $data);
    // }

    // /**
    //  * Takes a class and returns an array of its public and protected properties as an array with raw values.
    //  * @param object|string $data
    //  * @param bool $recursive If true, inner entities will be cast as array as well
    //  * @return array|null Array
    //  * @throws ReflectionException
    //  */
    // protected function objectToRawArray($data, bool $onlyChanged = true, bool $recursive = false): ?array
    // {
    //     $properties = parent::objectToRawArray($data, $onlyChanged);

    //     $primaryKey = null;

    //     if ($data instanceof Entity) {
    //         $cast = $data->cast();

    //         // Disable Entity casting, because raw primary key data is needed for database.
    //         $data->cast(false);

    //         $primaryKey = $data->{$this->primaryKey};

    //         // Restore Entity casting setting.
    //         $data->cast($cast);
    //     }

    //     // Always grab the primary key otherwise updates will fail.
    //     if (
    //         // @TODO Should use `$data instanceof Entity`.
    //         method_exists($data, 'toRawArray')
    //         && (
    //             !empty($properties)
    //             && !empty($this->primaryKey)
    //             && !in_array($this->primaryKey, $properties, true)
    //             && !empty($primaryKey)
    //         )
    //     ) {
    //         $properties[$this->primaryKey] = $primaryKey;
    //     }

    //     return $properties;
    // }
}
