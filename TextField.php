<?php

class TextField extends BaseField
{    
    protected $length;
    
    public function __construct($name, $displayName, $nullable = false, $length = 65535, $value = null)
    {
        parent::__construct($name, $displayName, $nullable, $value);
        $this->length = $length;
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
        else if (!$isNull && strlen($this->value) > $this->length)
        {
            $error = sprintf('%s must be %d characters or fewer.', $this->displayName, $this->length);
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
