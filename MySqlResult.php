<?php

/**
 * Represents a connection to a MySQL result.
 */
class MySqlResult implements IResult
{
    /**
     * The mysqli_result returned from a Select query
     */
    private $result;
    
    /**
     * Creates a new MySqlResult object.
     * 
     * @param mysqli_result $result
     *   The result to store
     */
    public function __construct($result)
    {
        if ($result && $result instanceof mysqli_result)
            $this->result = $result;
        else
            throw new DatabaseException('Specified result was invalid.');
    }
    
    /**
     * Frees results for the current object.
     */
    public function __destruct()
    {
        $this->Free();
    }
    
    /**
     * Implements IResult::Count().
     */
    public function Count()
    {
        if ($this->result)
            return $this->result->num_rows;
        return 0;
    }
    
    /**
     * Implements IResult::Free().
     */
    public function Free()
    {
        if ($this->result)
            $this->result->close();
        $this->result = NULL;
    }
    
    /**
     * Implements IResult::NextRowArray().
     */
    public function NextRowArray()
    {
        // make sure there is a result
        if (!$this->result) return NULL;
        // get the next row
        if ($row = $this->result->fetch_row())
            return $row;
        else
        {
            $this->Free();
            return NULL;
        }
    }
    
    /**
     * Implements IResult::NextRowAssoc().
     */
    public function NextRowAssoc()
    {
        // make sure there is a result
        if (!$this->result) return NULL;
        // get the next row
        if ($row = $this->result->fetch_assoc())
            return $row;
        else
        {
            $this->Free();
            return NULL;
        }
    }
    
    /**
     * Implements IResult::NextRowObject().
     */
    public function NextRowObject($className)
    {
        // make sure there is a result
        if (!$this->result) return NULL;
        // get the next row
        if ($row = $this->result->fetch_object($className))
            return $row;
        else
        {
            $this->Free();
            return NULL;
        }
    }    
}
?>
