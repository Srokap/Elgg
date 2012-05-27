<?php
/**
 * @author Paweł Sroka (pawel.sroka@vazco.eu)
 */
abstract class ElggDatabaseConnection {
	
	/**
	 * Remove a row from the database.
	 *
	 * @note Altering the DB invalidates all queries in {@link $DB_QUERY_CACHE}.
	 *
	 * @param string $query The SQL query to run
	 *
	 * @return int|false The number of affected rows or false on failure
	 * @access private
	 */
	public static function deleteData($query) {
		global $CONFIG, $DB_QUERY_CACHE;
		elgg_log("DB query $query", 'NOTICE');
		$dblink = self::getConnection('write');
		// Invalidate query cache
		if ($DB_QUERY_CACHE) {
			$DB_QUERY_CACHE->clear();
			elgg_log("Query cache invalidated", 'NOTICE');
		}
		if ($dblink->query("$query")) {
			return $dblink->getAffectedRowsCount();
		}
		return FALSE;
	}
	
	/**
	 * Update a row in the database.
	 *
	 * @note Altering the DB invalidates all queries in {@link $DB_QUERY_CACHE}.
	 *
	 * @param string $query The query to run.
	 *
	 * @return bool
	 * @access private
	 */
	public static function updateData($query) {
		global $CONFIG, $DB_QUERY_CACHE;
		elgg_log("DB query $query", 'NOTICE');
		$dblink = self::getConnection('write');
		// Invalidate query cache
		if ($DB_QUERY_CACHE) {
			$DB_QUERY_CACHE->clear();
			elgg_log("Query cache invalidated", 'NOTICE');
		}
		if ($dblink->query("$query")) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Insert a row into the database.
	 *
	 * @note Altering the DB invalidates all queries in {@link $DB_QUERY_CACHE}.
	 *
	 * @param mixed $query The query to execute.
	 *
	 * @return int|false The database id of the inserted row if a AUTO_INCREMENT field is
	 *                   defined, 0 if not, and false on failure.
	 * @access private
	 */
	public static function insertData($query) {
		global $CONFIG, $DB_QUERY_CACHE;
		elgg_log("DB query $query", 'NOTICE');
		$dblink = self::getConnection('write');
		// Invalidate query cache
		if ($DB_QUERY_CACHE) {
			$DB_QUERY_CACHE->clear();
		}
		elgg_log("Query cache invalidated", 'NOTICE');
		if ($dblink->query("$query")) {
			return $dblink->getInsertId();
		}
		return FALSE;
	}
	
	/**
	 * Establish a connection to the database servser
	 *
	 * Connect to the database server and use the Elgg database for a particular database link
	 *
	 * @param string $dblinkname The type of database connection. Used to identify the
	 * resource. eg "read", "write", or "readwrite".
	 *
	 * @return void
	 * @access private
	 */
	protected static function setupConnection($dblinkname = "readwrite") {
		// Get configuration, and globalise database link
		global $CONFIG, $dblink, $DB_QUERY_CACHE, $dbcalls;
		if ($dblinkname != "readwrite" && isset($CONFIG->db[$dblinkname])) {
			if (is_array($CONFIG->db[$dblinkname])) {
				$index = rand(0, sizeof($CONFIG->db[$dblinkname]));
				$dbhost = $CONFIG->db[$dblinkname][$index]->dbhost;
				$dbuser = $CONFIG->db[$dblinkname][$index]->dbuser;
				$dbpass = $CONFIG->db[$dblinkname][$index]->dbpass;
				$dbname = $CONFIG->db[$dblinkname][$index]->dbname;
			} else {
				$dbhost = $CONFIG->db[$dblinkname]->dbhost;
				$dbuser = $CONFIG->db[$dblinkname]->dbuser;
				$dbpass = $CONFIG->db[$dblinkname]->dbpass;
				$dbname = $CONFIG->db[$dblinkname]->dbname;
			}
		} else {
			$dbhost = $CONFIG->dbhost;
			$dbuser = $CONFIG->dbuser;
			$dbpass = $CONFIG->dbpass;
			$dbname = $CONFIG->dbname;
		}
		$dbType = isset($CONFIG->dbtype) ? $CONFIG->dbtype : 'mysql';
		$dbClassName = 'ElggDatabaseConnection'.elgg_strtoupper($dbType[0]).substr($dbType, 1);//make it camelcase
		// Connect to database
		$connection = new $dbClassName();
		$connection->connect($dbhost, $dbuser, $dbpass, $dbname);
		// Set DB for UTF8
		$connection->query("SET NAMES utf8");
		$dblink[$dblinkname] = $connection;
		$db_cache_off = FALSE;
		if (isset($CONFIG->db_disable_query_cache)) {
			$db_cache_off = $CONFIG->db_disable_query_cache;
		}
		// Set up cache if global not initialized and query cache not turned off
		if ((!$DB_QUERY_CACHE) && (!$db_cache_off)) {
			$DB_QUERY_CACHE = new ElggStaticVariableCache('db_query_cache');
		}
	}
	
	/**
	 * Establish database connections
	 *
	 * If the configuration has been set up for multiple read/write databases, set those
	 * links up separately; otherwise just create the one database link.
	 *
	 * @return void
	 * @access private
	 */
	public static function setupConnections() {
		global $CONFIG;
		if (!empty($CONFIG->db->split)) {
			self::setupConnection('read');
			self::setupConnection('write');
		} else {
			self::setupConnection('readwrite');
		}
	}
	
	/**
	 * Returns (if required, also creates) a database link resource.
	 *
	 * Database link resources are stored in the {@link $dblink} global.  These
	 * resources are created by {@link setup_db_connections()}, which is called if
	 * no links exist.
	 *
	 * @param string $dblinktype The type of link we want: "read", "write" or "readwrite".
	 *
	 * @return ElggDatabaseConnection
	 * @access private
	 */
	public static function getConnection($dblinktype) {
		global $dblink;
		if (isset($dblink[$dblinktype])) {
			return $dblink[$dblinktype];
		} elseif (isset($dblink['readwrite'])) {
			return $dblink['readwrite'];
		} else {
			self::setupConnections();
			return self::getConnection($dblinktype);
		}
	}
	
	/**
	 * Format a query string for logging
	 *
	 * @param string $query Query string
	 * @return string
	 * @access private
	 */
	public static function formatQuery($query) {
		// remove newlines and extra spaces so logs are easier to read
		return preg_replace('/\s\s+/', ' ', $query);
	}
	
	/**
	 * Execute a query.
	 *
	 * $query is executed via {@link mysql_query()}.  If there is an SQL error,
	 * a {@link DatabaseException} is thrown.
	 *
	 * @internal
	 * {@link $dbcalls} is incremented and the query is saved into the {@link $DB_QUERY_CACHE}.
	 *
	 * @param string $query  The query
	 * @param ElggDatabaseConnection   $dblink The DB link
	 *
	 * @return The result of mysql_query()
	 * @throws DatabaseException
	 * @access private
	 */
	public function executeQuery($query) {
		global $CONFIG, $dbcalls;
		if ($query == NULL) {
			throw new DatabaseException(elgg_echo('DatabaseException:InvalidQuery'));
		}
		$dbcalls++;
		$result = $this->query($query);
		if ($this->getErrorCode()) {
			throw new DatabaseException($this->getErrorMessage() . "\n\n QUERY: " . $query);
		}
		return $result;
	}
	
	/**
	 * Execute an EXPLAIN for $query.
	 *
	 * @param str   $query The query to explain
	 * @param ElggDatabaseConnection $link  The database link resource to user.
	 *
	 * @return mixed An object of the query's result, or FALSE
	 * @access private
	 */
	function explainQuery($query) {
		if ($this->executeQuery("explain " . $query)) {
			return $this->fetchObject();
		}
		return FALSE;
	}
	
	public abstract function connect($sqlserver, $sqluser, $sqlpassword, $database);
	public abstract function close();
	
	/**
	 * @param unknown_type $query
	 * @return mysqli_result
	 */
	public abstract function query($query);
	
	public abstract function getVersion($humanreadable = false);
	
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
	public abstract function fetchArray($resulttype=null);
	public abstract function getResultRowsCount();
	public abstract function freeResult();
}
?>