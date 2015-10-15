<?php

class TimeField extends BaseField
{
    public function __construct($name, $displayName, $nullable = false, $value = null)
    {
        parent::__construct($name, $displayName, $nullable, $value);
    }
    
    public function Validate(&$error)
    {
        // find if the value is null
        $isNull = is_null($this->value);
        
        // validate the field
        if ($isNull && !$this->nullable)
        {
            $error = sprintf('%s must have a value.', $this->displayName);
            return false;
        }
        else if (!$isNull && preg_match('/^\s*-?(\d{1,3}:)?(\d{1,2}:)?\d{1,2}\s*$/', $this->value) == 0)
        {
            $error = sprintf('%s must be a time.', $this->displayName);
            return false;
        }
        else
        {
            $error = null;
            return true; // no error
        }
    }
    
    public function ToQueryString(IDatabase $db)
    {
        $this->changed = false;
        return is_null($this->value) ? 'NULL' : "'" . $db->Escape($this->value) . "'";
    }
}

?>
