<?php

class BooleanField extends BaseField
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
        else
        {
            $error = null;
            return true; // no error
        }
    }
    
    public function ToQueryString(IDatabase $db)
    {
        $this->changed = false;
        // ==, not === ... don't care if the value is exactly true
        return is_null($this->value) ? 'NULL' : (int)($this->value == true);
    }
}

?>
