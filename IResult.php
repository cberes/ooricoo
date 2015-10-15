<?php
/**
 * Defines a common interface for a database result.
 */
interface IResult
{
    /**
     * Gets the number of rows in the result.
     * 
     * @return int
     *   The number of rows in the result.
     */
    public function Count();
    
    /**
     * Frees the stored result and sets it to NULL.
     */
    public function Free();
    
    /**
     * Returns the next row from the current result as an enumerated array.
     * 
     * If the result's end was reached, the result is freed.
     * 
     * @return array
     *   The row as an enumerated array, or NULL if the result's end was reached.
     */
    public function NextRowArray();
    
    /**
     * Returns the next row from the current result as an associate array.
     * 
     * If the result's end was reached, the result is freed.
     * 
     * @return array
     *   The row as an associative array, or NULL if the result's end was reached.
     */
    public function NextRowAssoc();
    
    /**
     * Returns the next row from the current result as the specified type
     * 
     * If the result's end was reached, the result is freed.
     * 
     * @param string $className
     *   The name of the class to which the row should be converted
     * 
     * @return object
     *   The row as the specified type, or NULL if the result's end was reached.
     */
    public function NextRowObject($className);
}
?>