<?php

namespace MVCME\Database;

use Closure;

interface DBConnectionInterface
{
    /**
     * Initializes the database connection/settings. 
     * @return void 
     */
    public function initialize();

    /**
     * Connect to the database.
     * @return false|object|resource
     */
    public function connect(bool $persistent = false);

    /**
     * Create a persistent database connection. 
     * @return false|object|resource 
     */
    public function persistentConnect();

    /**
     * Returns the actual connection object. If both a 'read' and 'write'
     * connection has been specified, you can pass either term in to
     * get that connection. If you pass either alias in and only a single
     * connection is present, it must return the sole connection.
     *
     * @return false|object|resource 
     */
    public function getConnection(?string $alias = null);

    /**
     * Returns the name of the current database being used.
     * @return string
     */
    public function getDatabase();

    /**
     * The name of the platform in use (MySQLi, Postgre, etc)
     * @return string
     */
    public function getPlatform();

    /**
     * Sets the Table Aliases to use. These are typically collected during use of the
     * Builder, and set here so queries are built correctly.
     * @return $this
     */
    public function setAliasedTables(array $aliases);

    /**
     * Add a table alias to our list.
     * @return $this
     */
    public function addTableAlias(string $table);

    /**
     * Orchestrates a query against the database. Queries must use
     * Database\Statement objects to store the query and build it. 
     * @param array|string|null $binds
     * @return BaseResult|bool|Query BaseResult when “read” type query, bool when “write” type query, Query when prepared query 
     */
    public function query(string $sql, $binds = null, bool $setEscapeFlags = true, string $queryClass = '');

    /**
     * Performs a basic query against the database. No binding or caching
     * is performed, nor are transactions handled. Simply takes a raw
     * query string and returns the database-specific result id.
     *
     * @return false|object|resource
     */
    public function simpleQuery(string $sql);


    /*
     * ---------------------------------------------
     * DATABASE TRANSACTION
     * ---------------------------------------------
     */

    /**
     * Disable Transactions 
     * This permits transactions to be disabled at run-time.
     */
    public function transOff();

    /**
     * Enable/disable Transaction Strict Mode
     *
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all subsequent groups will be
     * rolled back.
     *
     * If strict mode is disabled, each group is treated autonomously,
     * meaning a failure of one group will not affect any others
     *
     * @param bool $mode = true
     * @return $this
     */
    public function transStrict(bool $mode = true);

    /**
     * Start Transaction
     * @return bool
     */
    public function transStart(bool $testMode = false);

    /**
     * If set to true, exceptions are thrown during transactions. 
     * @return $this
     */
    public function transException(bool $transExcetion);

    /**
     * Complete Transaction
     * @return bool
     */
    public function transComplete();

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     * @return bool
     */
    public function transStatus();

    /**
     * Begin Transaction
     * @return bool
     */
    public function transBegin(bool $testMode = false);

    /**
     * Commit Transaction
     * @return bool
     */
    public function transCommit();

    /**
     * Rollback Transaction
     * @return bool
     */
    public function transRollback();



    /**
     * Returns a non-shared new instance of the query builder for this connection.
     * @param array|string $tableName
     * @return DBBuilder
     */
    public function table($tableName);

    /**
     * Returns a new instance of the DBBuilder class with a cleared FROM clause.
     * @return DBBuilder
     */
    public function newQuery();

    /**
     * Creates a prepared statement with the database that can then
     * be used to execute multiple statements against. Within the
     * closure, you would build the query in any normal way, though
     * the Query Builder is the expected manner.
     *
     * Example:
     *    $stmt = $db->prepare(function($db)
     *           {
     *             return $db->table('users')
     *                   ->where('id', 1)
     *                     ->get();
     *           })
     *
     * @return BasePreparedQuery|null
     */
    public function prepare(Closure $func, array $options = []);

    /**
     * Returns the last query's statement object.
     * @return Query
     */
    public function getLastQuery();

    /**
     * Returns a string representation of the last query's statement object.
     * @return string
     */
    public function showLastQuery();

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param array|string $item
     * @param bool $prefixSingle Prefix a table name with no segments?
     * @param bool $protectIdentifiers Protect table or column names?
     * @param bool $fieldExists Supplied $item contains a column name?
     *
     * @return array|string
     */
    public function protectIdentifiers($item, bool $prefixSingle = false, ?bool $protectIdentifiers = null, bool $fieldExists = true);

    /**
     * Escape the SQL Identifiers 
     * This function escapes column and table names 
     * @param array|string $item 
     * @return array|string
     */
    public function escapeIdentifiers($item);

    /**
     * Escapes data based on type. Sets boolean and null types
     * @param array|bool|float|int|object|string|null $str
     * @return array|float|int|string
     */
    public function escape($str);

    /**
     * Escape String
     * @param string|string[] $str Input string
     * @param bool $like Whether or not the string will be used in a LIKE condition 
     * @return string|string[]
     */
    public function escapeString($str, bool $like = false);

    /**
     * Escape LIKE String.
     * Calls the individual driver for platform specific escaping for LIKE conditions
     * @param string|string[] $str
     * @return string|string[]
     */
    public function escapeLikeString($str);

    /**
     * This function enables you to call PHP database functions
     * @param array ...$params
     * @return bool
     */
    public function callFunction(string $functionName, ...$params);


    // --------------------------------------------------------------------
    // META Methods
    // --------------------------------------------------------------------

    /**
     * Returns an array of table names 
     * @return array|false
     */
    public function listTables(bool $constrainByPrefix = false);

    /**
     * Determine if a particular table exists 
     * @param bool $cached Whether to use data cache
     * @return bool
     */
    public function tableExists(string $tableName, bool $cached = true);

    /**
     * Fetch Field Names 
     * @return array|false
     */
    public function getFieldNames(string $table);

    /**
     * Determine if a particular field exists
     * @return bool
     */
    public function fieldExists(string $fieldName, string $tableName);

    /**
     * Returns an object with field data 
     * @return stdClass[]
     */
    public function getFieldData(string $table);

    /**
     * Returns an object with key data 
     * @return array
     */
    public function getIndexData(string $table);

    /**
     * Returns an object with foreign key data 
     * @return array
     */
    public function getForeignKeyData(string $table);

    /**
     * Disables foreign key checks temporarily. 
     * @return bool
     */
    public function disableForeignKeyChecks();

    /**
     * Enables foreign key checks temporarily. 
     * @return bool
     */
    public function enableForeignKeyChecks();

    /**
     * Allows the engine to be set into a mode where queries are not
     * actually executed, but they are still generated, timed, etc. 
     * @return $this
     */
    public function pretend(bool $pretend = true);

    /**
     * Empties our data cache. Especially helpful during testing. 
     * @return $this
     */
    public function resetDataCache();

    /**
     * Determines if the statement is a write-type query or not. 
     * @param string $sql
     * @return bool
     */
    public function isWriteType($sql);
}
