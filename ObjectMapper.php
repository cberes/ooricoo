#!/usr/bin/php
<?php

function __autoload($class_name)
{
    include $class_name . '.php';
}

class ObjectMapper
{
    const REGION_CODE_BEGIN = '#region Auto-generated fields';
    const REGION_CODE_END = '#endregion Auto-generated fields';
    
    private $db;
    
    private $relativePath;

    public function __construct(MySqlDatabase $db, $relativePath)
    {
        $this->db = $db;
        $this->relativePath = $relativePath;
    }

    public function Map()
    {
        $res = $this->db->Select('SHOW FULL TABLES');
        while ($row = $res->NextRowArray())
        {
            if ($row[1] == 'BASE TABLE')
                $this->MapTable($row[0]);
        }
        $res->Free();
    }
    
    private function MapTable($tableName)
    {
        // get info on the table
        $res = $this->db->Select('SHOW COLUMNS FROM `' . $tableName . '`');
        if (!$res)
            return;
        
        // save the fields, types, the primary key, and anything else we need
        $fields = array();
        $key = '';
        while ($row = $res->NextRowAssoc())
        {
            // compile the field info
            $info = array();
            $info['type'] = $row['Type'];
            $info['null'] = strcmp($row['Null'], 'YES') == 0;
            $i = preg_match('/\((\d+)\)$/', $row['Type'], $matches);
            if ($i != 0)
                $info['length'] = (int)$matches[1];
            else
                $info['length'] = FALSE;
                        
            // save the field info
            $fields[$row['Field']] = $info;
            
            // check if this is the primary key
            if (strcmp($row['Key'], 'PRI') == 0)
                $key = $row['Field'];
        }
        $res->Free();
        
        // get the primary key
        $res = $this->db->Select('SHOW INDEX FROM `' . $tableName . "` WHERE Key_name = 'PRIMARY'");
        if (!$res) return;
        $row = $res->NextRowAssoc();
        $key = $row['Column_name'];
        $res->Free();
        
        // field names
        $fieldNames = array_keys($fields);
        
        // code
        // the hard part goes here
        // fields, getters, setters, constructor
        // update, delete
        $codeString  = self::REGION_CODE_BEGIN . PHP_EOL . PHP_EOL;
        
        // properties
        foreach ($fields as $field => $info)
        {
            $codeString .= 'private $' . $field . ';' . PHP_EOL;
        }
        $codeString .= 'private $fields;' . PHP_EOL;
        // constructor
        $codeString .= 'public function __construct() {' . PHP_EOL;
        $codeString .= '    parent::__construct();' . PHP_EOL;
        $codeString .= '    $this->fields = array("' . implode('", "', $fieldNames) . '");' . PHP_EOL;
        $codeString .= '    // mysqli->fetch_object() sets the members directly' . PHP_EOL;
        $codeString .= '    foreach ($this->fields as $field)' . PHP_EOL;
        $codeString .= '    {' . PHP_EOL;
        $codeString .= '        if (isset($this->{$field}))' . PHP_EOL;
        $codeString .= '        {' . PHP_EOL;
        $codeString .= '            $value = $this->{$field};' . PHP_EOL;
        $codeString .= '            // need to unset so the setter works correctly' . PHP_EOL;
        $codeString .= '            unset($this->{$field});' . PHP_EOL;
        $codeString .= '            $this->Set($field, $value);' . PHP_EOL;
        $codeString .= '        }' . PHP_EOL;
        $codeString .= '    }' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        // destructor
        $codeString .= 'public function __destruct() {' . PHP_EOL;
        $codeString .= '    parent::__destruct();' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        // mysqli_fetch_object() might need this
        $codeString .= 'public function Set($name, $value) {' . PHP_EOL;
        $codeString .= '    switch ($name)' . PHP_EOL;
        $codeString .= '    {' . PHP_EOL;
        foreach ($fields as $field => $info)
        {
            $codeString .= '       case ' . "'$field':" . PHP_EOL;
            $codeString .= '           $this->Set' . ucwords($field) . '($value);' . PHP_EOL;
            $codeString .= '           $this->' . $field . '->ResetChanged();' . PHP_EOL;
            $codeString .= '           break;' . PHP_EOL;
        }
        $codeString .= '    }' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        
        // static function to get a row of this type by its ID
        $codeString .= 'public static function Get' . ucwords($tableName) . 'Row(IDatabase $db, $id, $fields = "*") {' . PHP_EOL;
        $codeString .= '    $res = $db->Select("SELECT $fields FROM `' . $tableName . '` WHERE `' . $key . '` = $id;");' . PHP_EOL;
        $codeString .= '    if (!$res || $res->Count() == 0) return null;' . PHP_EOL;
        $codeString .= '    $row = $res->NextRowObject("' . $tableName . '");' . PHP_EOL;
        $codeString .= '    return $row;' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        
        // static function to get rows of this type by some criteria
        $codeString .= 'public static function Get' . ucwords($tableName) . 'Rows(IDatabase $db, $whereString, $fields = "*") {' . PHP_EOL;
        $codeString .= '    $res = $db->Select("SELECT $fields FROM `' . $tableName . '` WHERE $whereString;");' . PHP_EOL;
        $codeString .= '    if (!$res || $res->Count() == 0) return null;' . PHP_EOL;
        $codeString .= '    $rows = array();' . PHP_EOL;
        $codeString .= '    while ($row = $res->NextRowObject("' . $tableName . '"))' . PHP_EOL;
        $codeString .= '        $rows[] = $row;' . PHP_EOL;
        $codeString .= '    return $rows;' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        
        // getters
        foreach ($fields as $field => $info)
        {
            $codeString .= 'public function Get' . ucwords($field) . '() {' . PHP_EOL;
            $codeString .= '    return isset($this->' . $field . ') ? $this->' . $field . '->GetValue() : null ;' . PHP_EOL;
            $codeString .= '}' . PHP_EOL;
        }
        // set database
        //$codeString .= 'public function SetDatabaseMgr(IDatabase $db) {' . PHP_EOL;
        //$codeString .= '    $this->db = $db;' . PHP_EOL;
        //$codeString .= '}' . PHP_EOL;
        // setters
        $bools = array('false', 'true'); // false prints as an empty string, not 0
        foreach ($fields as $field => $info)
        {
            // 
            $name = $this->GetDisplayName($field);
            $nullable = $bools[(int)$info['null']];
            $length = $info['length'];
            // setter
            $codeString .= 'public function Set' . ucwords($field) . '($value) {' . PHP_EOL;
            $codeString .= '    if (isset($this->' . $field . '))' . PHP_EOL;
            $codeString .= '        $this->' . $field . '->SetValue($value);' . PHP_EOL;
            $codeString .= '    else' . PHP_EOL;
            $codeString .= '    {' . PHP_EOL;
            switch ($this->db->GetFieldType($info['type']))
            {
                case 'boolean':
                    $codeString .= '        $this->' . $field . ' = new BooleanField(' . "'$field', '$name', $nullable, " . '$value);' . PHP_EOL;
                    break;
                case 'date':
                    $codeString .= '        $this->' . $field . ' = new DateField(' . "'$field', '$name', $nullable, " . '$value);' . PHP_EOL;
                    break;
                case 'datetime':
                    $codeString .= '        $this->' . $field . ' = new DateTimeField(' . "'$field', '$name', $nullable, " . '$value);' . PHP_EOL;
                    break;
                case 'number':
                    $codeString .= '        $this->' . $field . ' = new NumberField(' . "'$field', '$name', $nullable, " . '$value);' . PHP_EOL;
                    break;
                case 'text':
                    $codeString .= '        $this->' . $field . ' = new TextField(' . "'$field', '$name', $nullable" . ($length === false ? '' : ", $length") . ');' . PHP_EOL;
                    $codeString .= '        $this->' . $field . '->SetValue($value);' . PHP_EOL;
                    break;
                case 'time':
                    $codeString .= '        $this->' . $field . ' = new TimeField(' . "'$field', '$name', $nullable, " . '$value);' . PHP_EOL;
                    break;
            }
            $codeString .= '    }' . PHP_EOL;
            $codeString .= '}' . PHP_EOL;
        }
        $codeString .= 'public function Delete(IDatabase $db) {' . PHP_EOL;
        $codeString .= '    return "DELETE FROM `' . $tableName . '` WHERE `' . $key . '` = " . $this->' . $key . '->ToQueryString($db) . ";";' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        $codeString .= 'public function Insert(IDatabase $db) {' . PHP_EOL;
        $codeString .= $this->db->BuildInsertString($tableName, '$this->fields', '$db');
        $codeString .= '}' . PHP_EOL;
        $codeString .= 'public function Update(IDatabase $db) {' . PHP_EOL;
        $codeString .= $this->db->BuildUpdateString($tableName, '$this->fields', '$db', $key);
        $codeString .= '}' . PHP_EOL;
        $codeString .= 'public function Validate(&$errors) {' . PHP_EOL;
        $codeString .= '    $success = true;' . PHP_EOL;
        $codeString .= '    $errors = array();' . PHP_EOL;
        $codeString .= '    foreach ($this->fields as $field)' . PHP_EOL;
        $codeString .= '    {' . PHP_EOL;
        $codeString .= '        if (isset($this->{$field}) && !$this->{$field}->Validate($error))' . PHP_EOL;
        $codeString .= '        {' . PHP_EOL;
        $codeString .= '            $errors[] = $error;' . PHP_EOL;
        $codeString .= '            $success = false;' . PHP_EOL;
        $codeString .= '        }' . PHP_EOL;
        $codeString .= '    }' . PHP_EOL;
        $codeString .= '    return $success;' . PHP_EOL;
        $codeString .= '}' . PHP_EOL;
        $codeString .= PHP_EOL . self::REGION_CODE_END;
        
        // output filename
        $filename = $this->relativePath . DIRECTORY_SEPARATOR . $tableName . '.php';
        
        
        $classString = file_get_contents($filename);
        $codeStringRegex = '|' . self::REGION_CODE_BEGIN . '\s*.*\s*' . self::REGION_CODE_END . '|is';
        $newFile = $classString === FALSE || preg_match($codeStringRegex, $classString) == 0;
            
        if ($newFile)
        {
            // create a new file
            $classString  = '<?php' . PHP_EOL . PHP_EOL;
            $classString .= 'class ' . ucwords($tableName) . ' extends DatabaseRow {' . PHP_EOL . PHP_EOL;
            $classString .= $codeString . PHP_EOL . PHP_EOL;
            $classString .= '}' . PHP_EOL . '?>' . PHP_EOL;
        }
        else
        {
            // replace regions in the existing file
            $classString = preg_replace($codeStringRegex, $codeString, $classString);
        }
        
        // write the file contents to the file
        $handle = fopen($filename, 'w');
        fwrite($handle, $classString);
        fclose($handle);
    }

    private function GetDisplayName($name)
    {
        // insert a space after a lowercase character that's followed by an uppercase character
        // also make the first character uppercase and replace underscores with spaces
        return preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', ucwords(trim(str_replace('_', ' ', $name))));
    }
}

if ($argc == 6)
{
    $db = new MySqlDatabase($argv[1], $argv[2], $argv[3], $argv[4]);
    $om = new ObjectMapper($db, $argv[5]);
    $om->Map();
}
else
    echo 'Usage: ObjectMapper.php dbHost dbUsername dbPassword dbName outputDirectory' . PHP_EOL;
?>
