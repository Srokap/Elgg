<?php
/**
 * @author Paweł Sroka (pawel.sroka@vazco.eu)
 */
class ElggDatabaseConnectionMysql extends ElggDatabaseConnection {
	/**
	 * Connection object.
	 * @var mysqli
	 */
	private $_connection;
	/**
	 * @var mysqli_result
	 */
	private $_last_result;
	private $RESULT_MODE = MYSQLI_STORE_RESULT;//MYSQLI_USE_RESULT
	
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
	
	public function getVersion($humanreadable = false) {
		if ($humanreadable) {
			return $this->_connection->server_info;
		} else {
			return $this->_connection->server_version;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ElggDatabaseConnection::query()
	 */
	public function query($query) {
		return $this->_last_result = $this->_connection->query($query, $this->RESULT_MODE);
	}
	
	public function close() {
		return $this->_connection->close();
	}
	
	public function getErrorCode() {
		return $this->_connection->errno;
	}
	
	public function getErrorMessage() {
		return $this->_connection->error;
	}
	
	public function getInsertId() {
		return $this->_connection->insert_id;
	}
	
	public function getAffectedRowsCount() {
		return $this->_connection->affected_rows;
	}
	
	public function sanitiseString($string) {
		return $this->_connection->real_escape_string($string);
	}
	
	//result handling
	
	public function fetchObject() {
		return $this->_last_result->fetch_object();
	}
	
	public function fetchAssoc() {
		return $this->_last_result->fetch_assoc();
	}
	
	public function fetchArray($resulttype=null) {
		return $this->_last_result->fetch_array($resulttype);
	}
	
	public function getResultRowsCount() {
		return $this->_last_result->fetch_assoc();
	}
	
	public function freeResult() {
		return $this->_last_result->free();
	}
}
?>