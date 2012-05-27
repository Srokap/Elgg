<?php
/**
 * @author Paweł Sroka (pawel.sroka@vazco.eu)
 */
abstract class ElggDatabaseConnection {
	public abstract function connect($sqlserver, $sqluser, $sqlpassword, $database);
	public abstract function close();
	public abstract function query($query);
	
	public abstract function getErrorCode();
	public abstract function getErrorMessage();
	
	public abstract function getInsertId();
	public abstract function getAffectedRowsCount();
	
	public abstract function sanitiseString($string);
	
	/**
	 * Sanitise a string for database use, but with the option of escaping extra characters.
	 *
	 * @param string $string           The string to sanitise
	 * @param string $extra_escapeable Extra characters to escape with '\\'
	 *
	 * @return string The escaped string
	 */
	public function sanitiseStringSpecial($string, $extra_escapeable = '') {
		$string = sanitise_string($string);
		for ($n = 0; $n < strlen($extra_escapeable); $n++) {
			$string = str_replace($extra_escapeable[$n], "\\" . $extra_escapeable[$n], $string);
		}
		return $string;
	}
	
	/**
	 * Sanitises an integer for database use.
	 *
	 * @param int  $int    Value to be sanitized
	 * @param bool $signed Whether negative values should be allowed (true)
	 * @return int
	 */
	public function sanitiseInt($int, $signed = true) {
		$int = (int) $int;
		if ($signed === false) {
			if ($int < 0) {
				$int = 0;
			}
		}
		return (int) $int;
	}
	
	public abstract function fetchObject();
	public abstract function fetchAssoc();
	public abstract function getResultRowsCount();
	public abstract function freeResult();
}
?>