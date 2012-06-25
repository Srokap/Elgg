<?php
/**
 * Base class for database connections, as well as helper class with database API for elgg.
 */
class ElggDatabase implements 
	DatabaseAccess
{
	
	private $connection;
	
	public function __construct(Zend_Db_Adapter_Abstract $conn) {
		$this->connection = $conn;
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
		$dbClassName = 'ElggDatabase'.elgg_strtoupper($dbType[0]).substr($dbType, 1);//make it camelcase
		// Connect to database
// 		$connection = new $dbClassName();
// 		$connection->connect($dbhost, $dbuser, $dbpass, $dbname);
		$implementor = new Zend_Db_Adapter_Mysqli(array(
			'dbname' => $dbname,
			'username' => $dbuser,
			'password' => $dbpass,
			'host' => $dbhost,
		));
		$connection = new ElggDatabase($implementor);
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
	 * @return ElggDatabase
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
	 * Retrieve rows from the database.
	 *
	 * Queries are executed with {@link execute_query()} and results
	 * are retrieved with {@link mysql_fetch_object()}.  If a callback
	 * function $callback is defined, each row will be passed as the single
	 * argument to $callback.  If no callback function is defined, the
	 * entire result set is returned as an array.
	 *
	 * @param mixed  $query    The query being passed.
	 * @param string $callback Optionally, the name of a function to call back to on each row
	 *
	 * @return array An array of database result objects or callback function results. If the query
	 *               returned nothing, an empty array.
	 * @access private
	 */
	public static function getData($query, $callback = "") {
		return self::queryRunner($query, $callback, false);
	}
	
	/**
	 * Retrieve a single row from the database.
	 *
	 * Similar to {@link get_data()} but returns only the first row
	 * matched.  If a callback function $callback is specified, the row will be passed
	 * as the only argument to $callback.
	 *
	 * @param mixed  $query    The query to execute.
	 * @param string $callback A callback function
	 *
	 * @return mixed A single database result object or the result of the callback function.
	 * @access private
	 */
	public static function getDataRow($query, $callback = "") {
		return self::queryRunner($query, $callback, true);
	}
	
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
	 * Handles returning data from a query, running it through a callback function,
	 * and caching the results. This is for R queries (from CRUD).
	 *
	 * @access private
	 *
	 * @param string $query    The query to execute
	 * @param string $callback An optional callback function to run on each row
	 * @param bool   $single   Return only a single result?
	 *
	 * @return array An array of database result objects or callback function results. If the query
	 *               returned nothing, an empty array.
	 * @since 1.8.0
	 * @access private
	 */
	public static function queryRunner($query, $callback = null, $single = false) {
		global $CONFIG, $DB_QUERY_CACHE;
		// Since we want to cache results of running the callback, we need to
		// need to namespace the query with the callback and single result request.
		// http://trac.elgg.org/ticket/4049
		$hash = (string)$callback . (int)$single . $query;
		// Is cached?
		if ($DB_QUERY_CACHE) {
			$cached_query = $DB_QUERY_CACHE[$hash];
	
			if ($cached_query !== FALSE) {
				elgg_log("DB query $query results returned from cache (hash: $hash)", 'NOTICE');
				return $cached_query;
			}
		}
		$dblink = ElggDatabase::getConnection('read');
		$return = array();
		if ($result = $dblink->executeQuery("$query")) {
			// test for callback once instead of on each iteration.
			// @todo check profiling to see if this needs to be broken out into
			// explicit cases instead of checking in the interation.
			$is_callable = is_callable($callback);
			while ($row = $result->fetchObject()) {
				if ($is_callable) {
					$row = $callback($row);
				}
	
				if ($single) {
					$return = $row;
					break;
				} else {
					$return[] = $row;
				}
			}
			$result->close();
		}
		if (empty($return)) {
			elgg_log("DB query $query returned no results.", 'NOTICE');
		}
		// Cache result
		if ($DB_QUERY_CACHE) {
			$DB_QUERY_CACHE[$hash] = $return;
			elgg_log("DB query $query results cached (hash: $hash)", 'NOTICE');
		}
		return $return;
	}
	
	/**
	 * Queue a query for execution upon shutdown.
	 *
	 * You can specify a handler function if you care about the result. This function will accept
	 * the raw result from {@link mysql_query()}.
	 *
	 * @param string   $query   The query to execute
	 * @param resource $dblink  The database link to use or the link type (read | write)
	 * @param string   $handler A callback function to pass the results array to
	 *
	 * @return true
	 * @access private
	 */
	public static function executeDelayedQuery($query, $dblink, $handler = "") {
		global $DB_DELAYED_QUERIES;
		if (!isset($DB_DELAYED_QUERIES)) {
			$DB_DELAYED_QUERIES = array();
		}
		if (!($dblink instanceof ElggDatabase) && $dblink != 'read' && $dblink != 'write') {
			return false;
		}
		// Construct delayed query
		$delayed_query = array();
		$delayed_query['q'] = $query;
		$delayed_query['l'] = $dblink;
		$delayed_query['h'] = $handler;
	
		$DB_DELAYED_QUERIES[] = $delayed_query;
		return TRUE;
	}
	
	/**
	 * Write wrapper for execute_delayed_query()
	 *
	 * @param string $query   The query to execute
	 * @param string $handler The handler if you care about the result.
	 *
	 * @return true
	 * @uses execute_delayed_query()
	 * @uses ElggDatabase::getConnection()
	 * @access private
	 */
	public static function executeDelayedWriteQuery($query, $handler = "") {
		return self::executeDelayedQuery($query, 'write', $handler);
	}
	
	/**
	 * Read wrapper for execute_delayed_query()
	 *
	 * @param string $query   The query to execute
	 * @param string $handler The handler if you care about the result.
	 *
	 * @return true
	 * @uses execute_delayed_query()
	 * @uses ElggDatabase::getConnection()
	 * @access private
	 */
	public static function executeDelayedReadQuery($query, $handler = "") {
		return self::executeDelayedQuery($query, 'read', $handler);
	}
	
	/**
	 * Execute any delayed queries upon shutdown.
	 *
	 * @return void
	 * @access private
	 */
	public static function delayedExecutionShutdownHook() {
		global $DB_DELAYED_QUERIES;
	
		foreach ($DB_DELAYED_QUERIES as $query_details) {
			try {
				$link = $query_details['l'];
	
				if ($link == 'read' || $link == 'write') {
					$link = ElggDatabase::getConnection($link);
				} elseif (!is_resource($link)) {
					elgg_log("Link for delayed query not valid resource or db_link type. Query: {$query_details['q']}", 'WARNING');
				}
					
				$result = $link->query($query_details['q']);
					
				if ((isset($query_details['h'])) && (is_callable($query_details['h']))) {
					$query_details['h']($result);
				}
			} catch (Exception $e) {
				// Suppress all errors since these can't be dealt with here
				elgg_log($e, 'WARNING');
			}
		}
	}
	
	/**
	 * Display profiling information about db at NOTICE debug level upon shutdown.
	 *
	 * @return void
	 * @access private
	 */
	public static function profilingShutdownHook() {
		global $dbcalls;
	
		// demoted to NOTICE as it corrupts javasript at DEBUG
		elgg_log("DB Queries for this page: $dbcalls", 'NOTICE');
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
	 * @param ElggDatabase   $dblink The DB link
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
	 * @param ElggDatabase $link  The database link resource to user.
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
	
	/**
	 * Returns error code of last operation or zero when no error occured.
	 * @return int
	 */
	function getErrorCode() {
		return (int)$this->lastSelect->errorCode();
	}
	
	/**
	 * Closes connection to database and cleans up.
	 * @return bool if operation was successful
	 */
	function close() {
		return $this->connection->closeConnection();
	}
	
	/**
	 * @var Zend_Db_Statement_Interface
	 */
	private $lastSelect;
	
	/**
	 * Run query against database.
	 * @param string $query query to run
	 * @return mixed implementation-specific result object
	 */
	function query($query) {
		return $this->lastSelect = $this->connection->query($query);
	}
	
	/**
	 * Returns version of server in two possible versions, depending on $humanreadable parameter value.
	 * @param bool $humanreadable if set to false, function MUST return int or float value that increases with higher versions of DB engine.
	 * @return int|string returns int value or string with human-redable DB version information
	 */
	function getVersion($humanreadable = false) {
		return $this->connection->getServerVersion();
	}
	
	/**
	 * Returns string describing the error or empty string if no error occured.
	 * @return string
	 */
	function getErrorMessage() {
		return $this->lastSelect->errorInfo();
	}
	
	function getInsertId() {
		return $this->connection->lastInsertId();
	}
	
	function getAffectedRowsCount() {
		return null;
	}
	
	function sanitiseString($string) {
		return $string;
	}
	
	function fetchObject() {
		return $this->connection->fetchRow($this->lastSelect);
	}
	
	function fetchAssoc() {
		return $this->connection->fetchAssoc($this->lastSelect);
	}
	
	function fetchArray($resulttype=null) {
		return $this->connection->fetchAll($this->lastSelect);
	}
	
	function getResultRowsCount() {
		return $this->lastSelect->rowCount();
	}
	
	function freeResult() {
		return $this->lastSelect->closeCursor();
	}
}
