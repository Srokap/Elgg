<?php
/**
 * Implements database interface for MySQL using mysqli object.
 */
class ElggDatabaseMysql extends ElggDatabase {
	/**
	 * Connection object.
	 * @var mysqli
	 */
	private $_connection;
	/**
	 * @var mysqli_result
	 */
	private $_last_result;
	/**
	 * Default result mode
	 * @var int
	 */
	private $RESULT_MODE = MYSQLI_STORE_RESULT;//MYSQLI_USE_RESULT
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::connect()
	 */
	public function connect($dbhost, $dbuser, $dbpass, $dbname) {
		$this->_connection = new mysqli($dbhost, $dbuser, $dbpass);
		if ($mysqli->connect_error!==null) {
// 			throw new DatabaseException("Connection Mysql error: ".$mysqli->connect_error);
			$msg = elgg_echo('DatabaseException:WrongCredentials', array($dbuser, $dbhost, "****"));
			throw new DatabaseException($msg);
		}
		if (!$this->_connection->select_db($dbname)) {
			$msg = elgg_echo('DatabaseException:NoConnect', array($dbname));
			throw new DatabaseException($msg);
		}
		// Set DB for UTF8
		$this->query("SET NAMES utf8");
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getVersion()
	 */
	public function getVersion($humanreadable = false) {
		if ($humanreadable) {
			return $this->_connection->server_info;
		} else {
			return $this->_connection->server_version;
		}
	}
	
	/**
	 * Run query against database.
	 * @param string $query query to run
	 * @see DatabaseAccess::query()
	 * @return mysqli_result
	 */
	public function query($query) {
		return $this->_last_result = $this->_connection->query($query, $this->RESULT_MODE);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::close()
	 */
	public function close() {
		return $this->_connection->close();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getErrorCode()
	 */
	public function getErrorCode() {
		return $this->_connection->errno;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getErrorMessage()
	 */
	public function getErrorMessage() {
		return $this->_connection->error;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getInsertId()
	 */
	public function getInsertId() {
		return $this->_connection->insert_id;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getAffectedRowsCount()
	 */
	public function getAffectedRowsCount() {
		return $this->_connection->affected_rows;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::sanitiseString()
	 */
	public function sanitiseString($string) {
		return $this->_connection->real_escape_string($string);
	}
	
	/* 
	 * Result handling 
	 */
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::fetchObject()
	 */
	public function fetchObject() {
		return $this->_last_result->fetch_object();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::fetchAssoc()
	 */
	public function fetchAssoc() {
		return $this->_last_result->fetch_assoc();
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $resulttype
	 * @return mixed
	 */
	public function fetchArray($resulttype=null) {
		return $this->_last_result->fetch_array($resulttype);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::getResultRowsCount()
	 */
	public function getResultRowsCount() {
		return $this->_last_result->fetch_assoc();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DatabaseAccess::freeResult()
	 */
	public function freeResult() {
		return $this->_last_result->free();
	}
}
