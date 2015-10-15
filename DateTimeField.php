<?php

class DateTimeField extends BaseField
{
    public function __construct($name, $displayName, $nullable = false, $value = null)
    {
        parent::__construct($name, $displayName, $nullable, $value);
    }
    
    // this might throw an exception
    public function GetValue()
    {
        if (is_object($this->value))
            return $this->value;
        else
            return new DateTime($this->value);
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
        
        if (!$isNull)
        {
            try
            {
                // if the given value is a string, this may throw an error when converting to a DateTime
                $this->GetValue();
            }
            catch (Exception $e)
            {
                $error = sprintf('%s must be a valid date/time.', $this->displayName);
                return false;
            }
        }        
        
        $error = null;
        return true; // no error
    }
    
    public function ToQueryString(IDatabase $db)
    {
        $this->changed = false;
        return is_null($this->value) ? 'NULL' : "'" . $this->GetValue()->format('Y-m-d H:i:s') . "'";
    }
}

?>
