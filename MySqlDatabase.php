<?php

/**
 * Represents a connection to a MySQL database.
 */
class MySqlDatabase implements IDatabase
{
    /**
     * The MySQL database object
     */
    private $mysqli;
    
    /**
     * Creates a new MySqlDatabase object.
     * 
     * @param string $server
     *   The database server
     * @param string $username
     *   The database username
     * @param string $password
     *   The database user's password
     * @param string $database
     *   The name of the database to connect to
     */
    public function __construct($server, $username, $password, $database)
    {
        $this->mysqli = new mysqli($server, $username, $password, $database);
        if ($this->mysqli->connect_errno)
            throw new DatabaseException('Could not connect to MySQL: ' . $this->mysqli->connect_error);
    }
    
    /**
     * Frees results for the current object.
     */
    public function __destruct()
    {
        // close the link if it's open
        if ($this->mysqli && !$this->mysqli->connect_errno)
        {
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }
    
    /**
     * Implements IDatabase::BuildInsertString().
     */
    public function BuildInsertString($tableName, $fields, $db)
    {
        $functionString  = '$changedFields = array();' . PHP_EOL;
        $functionString .= 'foreach (' . $fields . ' as $field)' . PHP_EOL;
        $functionString .= '{' . PHP_EOL;
        $functionString .= '    if (isset($this->{$field}))' . PHP_EOL;
        $functionString .= '        $changedFields[] = $field;' . PHP_EOL;
        $functionString .= '}' . PHP_EOL;
        $functionString .= '$fieldValues = array();' . PHP_EOL;
        $functionString .= 'foreach (' . $fields . ' as $field)' . PHP_EOL;
        $functionString .= '{' . PHP_EOL;
        $functionString .= '    if (isset($this->{$field}))' . PHP_EOL;
        $functionString .= '        $fieldValues[] = $this->{$field}->ToQueryString(' . $db . ');' . PHP_EOL;
        $functionString .= '}' . PHP_EOL;
        $functionString .= 'if (count($fieldValues) == 0) return null;' . PHP_EOL;
        $functionString .= 'return "INSERT INTO `' . $tableName . '` (`" . implode("`,`", $changedFields) . "`) VALUES (" . implode(",", $fieldValues) . ");";' . PHP_EOL;
        return $functionString;
    }
    
    /**
     * Implements IDatabase::BuildInsertString().
     */
    public function BuildUpdateString($tableName, $fields, $db, $key)
    {
        $functionString  = '$fieldValues = array();' . PHP_EOL;
        $functionString .= 'foreach (' . $fields . ' as $field)' . PHP_EOL;
        $functionString .= '{' . PHP_EOL;
        $functionString .= '    if (isset($this->{$field}) && $this->{$field}->IsChanged() && strcmp($field, "' . $key . '") != 0)' . PHP_EOL;
        $functionString .= '        $fieldValues[] = $field . " = " . $this->{$field}->ToQueryString(' . $db . ');' . PHP_EOL;
        $functionString .= '}' . PHP_EOL;
        $functionString .= 'if (count($fieldValues) == 0) return null;' . PHP_EOL;
        $functionString .= 'return "UPDATE `' . $tableName . '` SET " . implode(", ", $fieldValues) . " WHERE `' . $key . '` = " . $this->' . $key . '->ToQueryString(' . $db . ') . ";";' . PHP_EOL;
        return $functionString;
    }
    
    /**
     * Implements IDatabase::Escape().
     */
    public function Escape($value)
    {
        // check that the link is okay
        if ($this->mysqli->connect_errno) return $value;
        // escape the string
        return $this->mysqli->real_escape_string($value);
    }
    
    /**
     * Implements IDatabase::Execute().
     */
    public function Execute($query)
    {
        if (!$query) return 0;
        // check that the link is okay
        if ($this->mysqli->connect_errno) return 0;
        // execute the query
        if (!$this->mysqli->query($query))
            throw new DatabaseException('Invalid query: ' . $this->mysqli->error);
        return $this->mysqli->affected_rows;
    }
    
    /**
     * Implements IDatabase::GetFieldType().
     */
    public function GetFieldType($type)
    {
        // work in lowercase
        $type = strtolower($type);
        
        // booleans
        switch ($type) {
            case 'tinyint(1)':
            case 'bit(1)':
            case 'boolean':
            case 'bool':
                return 'boolean';
                break;
        }
        
        // strip the size
        $pos = strpos($type, '(');
        if ($pos !== FALSE)
            $type = substr($type, 0, $pos);
        
        // remove any modifiers like big, small, etc.
        $type = preg_replace('/^(big)|(long)|(medium)|(small)|(tiny)|(var)/i', '', $type);
        
        switch ($type) {
            // text
            case 'binary':
            case 'blob':
            case 'char':
            case 'enum':
            case 'set':
            case 'text':
                return 'text';
                break;
            // dates/times
            case 'date':
            case 'time':
                return $type;
                break;
            case 'datetime':
            case 'timestamp': // timestamp is has a smaller range, but whatever
                return 'datetime';
                break;
        }
        return 'number';
    }
    
    /**
     * Implements IDatabase::Insert().
     */
    public function Insert($query, &$id)
    {
        if (!$query) return 0;
        // check that the link is okay
        if ($this->mysqli->connect_errno) return 0;
        // execute the query
        if (!$this->mysqli->query($query))
            throw new DatabaseException('Invalid query: ' . $this->mysqli->error);
        $id = $this->mysqli->insert_id;
        return $this->mysqli->affected_rows;
    }
    
    /**
     * Implements IDatabase::Select().
     */
    public function Select($query, $useResult = false)
    {
        if (!$query) return null;
        // check that the link is okay
        if ($this->mysqli->connect_errno) return NULL;
        // result mode
        $mode = MYSQLI_STORE_RESULT;
        if ($useResult) $mode = MYSQLI_USE_RESULT;
        // execute the query
        if ($result = $this->mysqli->query($query, $mode))
            return new MySqlResult($result);
        else
            throw new DatabaseException('Invalid query: ' . $this->mysqli->error);
    }
}
?>
