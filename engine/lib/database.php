<?php
/**
 * Elgg database procedural code.
 *
 * Includes functions for establishing and retrieving a database link,
 * reading data, writing data, upgrading DB schemas, and sanitizing input.
 *
 * @package Elgg.Core
 * @subpackage Database
 */

/**
 * Query cache for all queries.
 *
 * Each query and its results are stored in this array as:
 * <code>
 * $DB_QUERY_CACHE[$query] => array(result1, result2, ... resultN)
 * </code>
 *
 * @global array $DB_QUERY_CACHE
 */
global $DB_QUERY_CACHE;
$DB_QUERY_CACHE = array();

/**
 * Queries to be executed upon shutdown.
 *
 * These queries are saved to an array and executed using
 * a function registered by register_shutdown_function().
 *
 * Queries are saved as an array in the format:
 * <code>
 * $DB_DELAYED_QUERIES[] = array(
 * 	'q' => str $query,
 * 	'l' => resource $dblink,
 * 	'h' => str $handler // a callback function
 * );
 * </code>
 *
 * @global array $DB_DELAYED_QUERIES
 */
global $DB_DELAYED_QUERIES;
$DB_DELAYED_QUERIES = array();

/**
 * Database connection resources.
 *
 * Each database link created with establish_db_link($name) is stored in
 * $dblink as $dblink[$name] => resource.  Use ElggDatabase::getConnection($name) to retrieve it.
 *
 * @global array $dblink
 */
global $dblink;
$dblink = array();

/**
 * Database call count
 *
 * Each call to the database increments this counter.
 *
 * @global integer $dbcalls
 */
global $dbcalls;
$dbcalls = 0;

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
function establish_db_link($dblinkname = "readwrite") {
	return ElggDatabase::getConnection($dblinkname);
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
function setup_db_connections() {
	return ElggDatabase::setupConnections();
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
 * @return object Database link
 * @access private
 */
function get_db_link($dblinktype) {
	return ElggDatabase::getConnection($dblinktype);
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
function explain_query($query, $link) {
	return $link->explainQuery($query);
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
function execute_query($query, $dblink) {
	return $dblink->executeQuery($query);
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
function execute_delayed_query($query, $dblink, $handler = "") {
	return ElggDatabase::executeDelayedQuery($query, $dblink, $handler);
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
function execute_delayed_write_query($query, $handler = "") {
	return ElggDatabase::executeDelayedWriteQuery($query, $handler);
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
function execute_delayed_read_query($query, $handler = "") {
	return ElggDatabase::executeDelayedReadQuery($query, $handler);
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
function get_data($query, $callback = "") {
	return ElggDatabase::getData($query, $callback);
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
function get_data_row($query, $callback = "") {
	return ElggDatabase::getDataRow($query, $callback, true);
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
function elgg_query_runner($query, $callback = null, $single = false) {
	return ElggDatabase::queryRunner($query, $callback, $single);
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
function insert_data($query) {
	return ElggDatabase::insertData($query);
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
function update_data($query) {
	return ElggDatabase::updateData($query);
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
function delete_data($query) {
	return ElggDatabase::deleteData($query);
}


/**
 * Return tables matching the database prefix {@link $CONFIG->dbprefix}% in the currently
 * selected database.
 *
 * @return array|false List of tables or false on failure
 * @static array $tables Tables found matching the database prefix
 * @access private
 */
function get_db_tables() {
	global $CONFIG;
	static $tables;

	if (isset($tables)) {
		return $tables;
	}

	try{
		$result = ElggDatabase::getData("show tables like '" . $CONFIG->dbprefix . "%'");
	} catch (DatabaseException $d) {
		// Likely we can't handle an exception here, so just return false.
		return FALSE;
	}

	$tables = array();

	if (is_array($result) && !empty($result)) {
		foreach ($result as $row) {
			$row = (array) $row;
			if (is_array($row) && !empty($row)) {
				foreach ($row as $element) {
					$tables[] = $element;
				}
			}
		}
	} else {
		return FALSE;
	}

	return $tables;
}

/**
 * Optimise a table.
 *
 * Executes an OPTIMIZE TABLE query on $table.  Useful after large DB changes.
 *
 * @param string $table The name of the table to optimise
 *
 * @return bool
 * @access private
 */
function optimize_table($table) {
	$table = sanitise_string($table);
	return ElggDatabase::updateData("optimize table $table");
}

/**
 * Get the last database error for a particular database link
 *
 * @param ElggDatabase $dblink The DB link
 *
 * @return string Database error message
 * @access private
 */
function get_db_error($dblink) {
	$dblink->getErrorMessage();
}

/**
 * Runs a full database script from disk.
 *
 * The file specified should be a standard SQL file as created by
 * mysqldump or similar.  Statements must be terminated with ;
 * and a newline character (\n or \r\n) with only one statement per line.
 *
 * The special string 'prefix_' is replaced with the database prefix
 * as defined in {@link $CONFIG->dbprefix}.
 *
 * @warning Errors do not halt execution of the script.  If a line
 * generates an error, the error message is saved and the
 * next line is executed.  After the file is run, any errors
 * are displayed as a {@link DatabaseException}
 *
 * @param string $scriptlocation The full path to the script
 *
 * @return void
 * @throws DatabaseException
 * @access private
 */
function run_sql_script($scriptlocation) {
	if ($script = file_get_contents($scriptlocation)) {
		global $CONFIG;

		$errors = array();

		// Remove MySQL -- style comments
		$script = preg_replace('/\-\-.*\n/', '', $script);

		// Statements must end with ; and a newline
		$sql_statements = preg_split('/;[\n\r]+/', $script);

		foreach ($sql_statements as $statement) {
			$statement = trim($statement);
			$statement = str_replace("prefix_", $CONFIG->dbprefix, $statement);
			if (!empty($statement)) {
				try {
					$result = ElggDatabase::updateData($statement);
				} catch (DatabaseException $e) {
					$errors[] = $e->getMessage();
				}
			}
		}
		if (!empty($errors)) {
			$errortxt = "";
			foreach ($errors as $error) {
				$errortxt .= " {$error};";
			}

			$msg = elgg_echo('DatabaseException:DBSetupIssues') . $errortxt;
			throw new DatabaseException($msg);
		}
	} else {
		$msg = elgg_echo('DatabaseException:ScriptNotFound', array($scriptlocation));
		throw new DatabaseException($msg);
	}
}

/**
 * Format a query string for logging
 * 
 * @param string $query Query string
 * @return string
 * @access private
 */
function elgg_format_query($query) {
	return ElggDatabase::formatQuery($query);
}

/**
 * Sanitise a string for database use, but with the option of escaping extra characters.
 *
 * @param string $string           The string to sanitise
 * @param string $extra_escapeable Extra characters to escape with '\\'
 *
 * @return string The escaped string
 */
function sanitise_string_special($string, $extra_escapeable = '') {
	$dblink = ElggDatabase::getConnection('write');
	return $dblink->sanitiseStringSpecial($string, $extra_escapeable);
}

/**
 * Sanitise a string for database use.
 *
 * @param string $string The string to sanitise
 *
 * @return string Sanitised string
 */
function sanitise_string($string) {
	$dblink = ElggDatabase::getConnection('write');
	// @todo does this really need the trim?
	// there are times when you might want trailing / preceeding white space.
	return $dblink->sanitiseString(trim($string));
}

/**
 * Wrapper function for alternate English spelling
 *
 * @param string $string The string to sanitise
 *
 * @return string Sanitised string
 */
function sanitize_string($string) {
	return sanitise_string($string);
}

/**
 * Sanitises an integer for database use.
 *
 * @param int  $int    Value to be sanitized
 * @param bool $signed Whether negative values should be allowed (true)
 * @return int
 */
function sanitise_int($int, $signed = true) {
	$dblink = ElggDatabase::getConnection('write');
	return $dblink->sanitiseInt($int, $signed);
}

/**
 * Sanitizes an integer for database use.
 * Wrapper function for alternate English spelling (@see sanitise_int)
 *
 * @param int  $int    Value to be sanitized
 * @param bool $signed Whether negative values should be allowed (true)
 * @return int
 */
function sanitize_int($int, $signed = true) {
	$dblink = ElggDatabase::getConnection('write');
	return $dblink->sanitiseInt($int, $signed);
}

function db_delayedexecution_shutdown_hook() {
	ElggDatabase::delayedExecutionShutdownHook();
}

/**
 * Registers shutdown functions for database profiling and delayed queries.
 *
 * @access private
 */
function init_db() {
	register_shutdown_function(array('ElggDatabase', 'delayedExecutionShutdownHook'));
	register_shutdown_function(array('ElggDatabase', 'profilingShutdownHook'));
}

elgg_register_event_handler('init', 'system', 'init_db');
