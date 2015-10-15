<?php
/**
 * Defines a common interface for a database connection.
 */
interface IDatabase
{
    public function BuildInsertString($tableName, $fields, $db);
    
    public function BuildUpdateString($tableName, $fields, $db, $key);
    
    /**
     * Escapes the specific value for use in queries.
     * 
     * @param string $value
     *   The value to escape
     * 
     * @return string
     *   The escaped value
     */
    public function Escape($value);
    
    /**
     * Executes the specified query.
     * 
     * Intended for Update and Delete queries
     * 
     * @param string $query
     *   The query to execute
     * 
     * @return int
     *   The number of affected rows
     */
    public function Execute($query);
    
    /**
     * Gets the simplified version of the specified field type.
     * 
     * @param string $type
     *   The field type to check
     * 
     * @return string
     *   The simplified version of the specified field type
     */
    public function GetFieldType($type);
    
    /**
     * Executes the specified query.
     * 
     * Intended for Insert queries
     * 
     * @param string $query
     *   The query to execute
     * @param int $id
     *   The ID of the newly inserted row
     * 
     * @return int
     *   The number of affected rows
     */
    public function Insert($query, &$id);
    
    /**
     * Executes the specified query.
     * 
     * Intended for Select queries
     * 
     * @param $query
     *   The query to execute
     * @param bool $useResult
     *   (optional) Whether the result should unbuffered (for large datasets)
     * 
     * @return IResult
     *   The result object
     */
    public function Select($query, $useResult = false);
}
?>
