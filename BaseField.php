<?php

abstract class BaseField
{
    protected $changed;
    
    protected $displayName;
    
    protected $name;
    
    protected $nullable;
    
    protected $value;
    
    public function __construct($name, $displayName, $nullable = false, $value = null)
    {
        // it is assumed that the value passed to the constructor comes from the database
        $this->changed = true;
        $this->displayName = $displayName;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->value = $value;
    }
    
    public function GetValue()
    {
        return $this->value;
    }
    
    public function IsChanged()
    {
        return $this->changed;
    }
    
    public function ResetChanged()
    {
        $this->changed = false;
    }
    
    public function SetValue($value)
    {
        // it is assumed that the value passed to this function is a new value
        $this->value = $value;
        $this->changed = true;
    }
    
    abstract public function Validate(&$error);
    
    abstract public function ToQueryString(IDatabase $db);
}

?>
