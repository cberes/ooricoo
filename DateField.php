<?php

class DateField extends DateTimeField
{
    public function __construct($name, $displayName, $nullable = false, $value = null)
    {
        parent::__construct($name, $displayName, $nullable, $value);
    }
    
    public function ToQueryString(IDatabase $db)
    {
        $this->changed = false;
        return is_null($this->value) ? 'NULL' : "'" . $this->GetValue()->format('Y-m-d') . "'";
    }
}

?>
