<?php

abstract class DatabaseRow
{
    public function __construct()
    {
    }
    
    public function __destruct()
    {
    }
    
    abstract public function Delete(IDatabase $db);
    
    abstract public function Insert(IDatabase $db);
    
    abstract public function Update(IDatabase $db);
    
    abstract public function Validate(&$errors);
}
?>
