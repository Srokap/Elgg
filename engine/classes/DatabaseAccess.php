<?php
/**
 * Interface for SQL database connections.
 */
interface DatabaseAccess {
	
	/**
	 * Setup new connection with database.
	 * @param string $sqlserver host address
	 * @param string $sqluser database user name
	 * @param string $sqlpassword database password for user
	 * @param string $database database name to be selected
	 * @return mixed implementation-specific connection object
	 */
	public abstract function connect($sqlserver, $sqluser, $sqlpassword, $database);
	
	/**
	 * Closes connection to database and cleans up.
	 * @return bool if operation was successful
	 */
	public abstract function close();
	
	/**
	 * Run query against database.
	 * @param string $query query to run
	 * @return mixed implementation-specific result object
	 */
	public abstract function query($query);
	
	/**
	 * Returns version of server in two possible versions, depending on $humanreadable parameter value.
	 * @param bool $humanreadable if set to false, function MUST return int or float value that increases with higher versions of DB engine.
	 * @return int|string returns int value or string with human-redable DB version information
	 */
	public abstract function getVersion($humanreadable = false);
	
	/**
	 * Returns error code of last operation or zero when no error occured. 
	 * @return int
	 */
	public abstract function getErrorCode();
	
	/**
	 * Returns string describing the error or empty string if no error occured.
	 * @return string
	 */
	public abstract function getErrorMessage();
	
	/**
	 * 
	 */
	public abstract function getInsertId();
	
	public abstract function getAffectedRowsCount();
	
	public abstract function sanitiseString($string);
	
	public abstract function fetchObject();
	
	public abstract function fetchAssoc();
	
	public abstract function fetchArray($resulttype=null);
	
	public abstract function getResultRowsCount();
	
	public abstract function freeResult();
}