<?php
/**
 * Interface for SQL database connections.
 */
interface DatabaseAccess {
	
	/**
	 * Closes connection to database and cleans up.
	 * @return bool if operation was successful
	 */
	function close();
	
	/**
	 * Run query against database.
	 * @param string $query query to run
	 * @return mixed implementation-specific result object
	 */
	function query($query);
	
	/**
	 * Returns version of server in two possible versions, depending on $humanreadable parameter value.
	 * @param bool $humanreadable if set to false, function MUST return int or float value that increases with higher versions of DB engine.
	 * @return int|string returns int value or string with human-redable DB version information
	 */
	function getVersion($humanreadable = false);
	
	/**
	 * Returns error code of last operation or zero when no error occured. 
	 * @return int
	 */
	function getErrorCode();
	
	/**
	 * Returns string describing the error or empty string if no error occured.
	 * @return string
	 */
	function getErrorMessage();
	
	/**
	 * 
	 */
	function getInsertId();
	
	function getAffectedRowsCount();
	
	function sanitiseString($string);
	
	function fetchObject();
	
	function fetchAssoc();
	
	function fetchArray($resulttype=null);
	
	function getResultRowsCount();
	
	function freeResult();
}