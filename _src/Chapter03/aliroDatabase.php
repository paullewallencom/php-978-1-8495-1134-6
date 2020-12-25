<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the 
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more 
 * information on this, please see the index.php file or visit 
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * Everything here is to do with database management.
 *
 * aliroDataCache is not yet used - it exists ready for development into a cache
 * for database queries.  This has low priority or may even be abandonded, since
 * it is usually more effective to cache complete output, or more structured
 * data derived from the database, as happens in cached singletons.
 *
 * databaseException uses PHP5 exception handling for database errors, rather
 * than expecting other applications to handle them.  This is combined with the
 * introduction of an error logging table, since detailed diagnostic information
 * is useful to developers, but not much use to users.  Only basic messages are
 * shown to users.
 *
 * aliroBasicDatabase contains all the basic functions to make it easier to
 * code database functions.  It selects an interface class, using either
 * the mysqli or mysql PHP interface mechanisms.
 *
 * database is a class provided for backwards compatibility with Mambo 4.x and
 * Joomla! 1.0.x.  aliroDatabaseHandler is simply the preferred name for a class
 * with the same functions as "database".
 *
 * aliroDatabase is a singleton extension of the abstract database class.  It is
 * created from the stored credentials for the general database driving Aliro.
 *
 * aliroCoreDatabase is another singleton extension of the abstract database class.
 * It is the optionally separate database holding critical tables relating only to
 * the core of Aliro, such as information about menus, components, etc.  It is also
 * the only place where user passwords are stored, thus reducing the impact of
 * SQL injection attacks that penetrate only the general database.  If it is not
 * possible to have two databases, Aliro will run with both being the same.
 *
 * Other names are purely for compatibility and are deprecated.
 *
 */

final class aliroDataCache {
	public $records = array();
}

final class databaseException extends Exception {
	public $dbname = '';
	public $sql = '';
	public $number = 0;

	public function __construct ($dbname, $message, $sql, $number, $dbtrace) {
		parent::__construct($message, $number);
		$this->dbname = $dbname;
		$this->sql = $sql;
		$this->dbtrace = $dbtrace;
	}

}

class aliroBasicDatabase {
	protected static $stats = array();
	protected $_sql='';
	protected $_cached=false;
	protected $_errorNum=0;
	protected $_errorMsg='';
	protected $_table_prefix='';
	protected $_resource='';
	protected $_cursor=null;
	protected $_log=array();
	protected $DBname = '';
	protected $interface = null;
	private $host = '';
	private $user = '';
	private $pass = '';
	private $requestTime = 0;
	private $logAll = false;

	public function __construct( $host, $user, $pass, $db, $table_prefix, $return_on_error=false ) {
		$this->requestTime = time();
		// perform a number of fatality checks, then die gracefully if necessary
		$this->DBname = $db;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->_table_prefix = $table_prefix;
		if (!$this->interface = aliroDatabaseHandler::getInterface($db)) {
			if ($return_on_error) {
				$this->_errorNum = _ALIRO_DB_NO_INTERFACE;
				return;
			}
			$this->forceOffline(_ALIRO_DB_NO_INTERFACE);
		}
		$this->connectToDB ($return_on_error);
		if (!$this->_resource) return;
		$this->interface->setCharset('utf8');
		clearstatcache();
	}
	
	protected function __clone () {
		// Enforce singleton
	}
	
	protected function connectToDB ($return_on_error=false) {
		if (!($this->_resource = $this->interface->connect($this->host, $this->user, $this->pass, $this->DBname))) {
			$this->_errorMsg = $this->interface->connectError();
			if ($return_on_error) {
				$this->_errorNum = _ALIRO_DB_CONNECT_FAILED;
				return;
			}
			$this->forceOffline(_ALIRO_DB_CONNECT_FAILED);
		}
	}

	public function __destruct () {
		try {
			@session_write_close();
			if (aliro::getInstance()->installed) $this->saveStats();
    	} catch (databaseException $exception) {
    		if (_ALIRO_IS_ADMIN) {
    			echo $exception->getMessage();
    		}
    		exit('DB Error during shutdown');
    	}
	}

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}
	
	public function getName () {
		return $this->DBname;
	}

	public function setFieldValue ($data, $type='varchar') {
		return $this->interface->setFieldValue($data, $type);
	}

	protected function forceOffline ($error_number) {
			$offline = new aliroOffline ();
			$offline->show($error_number);
			// Uncomment this for more diagnostics
			// echo aliroRequest::trace();
			exit();
	}
	
	public function defaultDate () {
		return $this->interface->defaultDate();
	}
	
	public function dateNow () {
		return $this->interface->dateNow();
	}

	// Deprecated in favour of leaving all error handling to the system
	public function getErrorNum() {
		return $this->_errorNum;
	}

	// Deprecated as above
	public function getErrorMsg() {
		return str_replace( array( "\n", "'" ), array( '\n', "'" ), $this->_errorMsg );
	}

	// Takes a string and escapes any characters needing it
	public function getEscaped($text) {
		return $this->interface->getEscaped($text);
	}

	// Deprecated - does not add enough value
	public function Quote( $text ) {
		return '\''.$this->getEscaped($text).'\'';
	}

	// No conversion of prefix marker - use only internally within Aliro DB framework
	public function setBareQuery($sql) {
		$this->_sql = $sql;
	}

	// Replaces #_ by the chosen database prefix and saves the query
	public function setQuery( $sql, $cached=false, $prefix='#__' ) {
		$this->_sql = $this->replacePrefix($sql, $prefix);
		$this->_cached = $cached;
	}

	// Carries out prefix marker replacement
	public function replacePrefix ($sql, $prefix='#__') {
		$text = $sql;
		$result = '';
		while ($text) {
			$firstquote = $this->nonzeromin(strpos($text, "'"), strpos($text, '"'));
			if ($firstquote) {
				$result .= str_replace($prefix, $this->_table_prefix, substr($text,0,$firstquote));
				$text = substr($text, $firstquote);
				$endquote = $this->findMatchingQuote($text, $text[0]);
				$result .= substr($text, 0, $endquote+1);
				$text = substr($text, $endquote+1);
			}
			else {
				$result .= str_replace($prefix, $this->_table_prefix, $text);
				break;
			}
		}
		return $result;
	}

	protected function nonzeromin ($x, $y) {
		if (false === $x) return $y;
		if (false === $y) return $x;
		return min($x, $y);
	}

	protected function findMatchingQuote ($text, $quote) {
		$skip = 1;
		do {
			$endquote = $quote ? strpos($text, $quote, $skip) : strlen($text) - 1;
			if ($endquote) $skip = $endquote+1;
		}
		while ($endquote AND '\\' == $text[$endquote-1]);
		if ($endquote) return $endquote;
		else return strlen($text)-1;
	}

	public function restoreOnePrefix ($tablename) {
		if (substr($tablename, 0, strlen($this->_table_prefix)) === $this->_table_prefix) return '#__'.substr($tablename, strlen($this->_table_prefix));
		else return $tablename;
	}

	// Returns stored SQL with replacements, ready to display
	public function getQuery ($sql='') {
		if ($sql == '') $sql = $this->_sql;
		return "<pre>" . htmlspecialchars( $sql ) . "</pre>";
	}

	public function query ($sql='') {
		if (empty($sql)) $sql = $this->_sql;
		$timer = new aliroProfiler('Database timer');
        $this->_cursor = $this->doQueryWork($timer, $sql);
		if ($this->_cursor) {
			if ($this->logAll) {
				$sql = $this->replacePrefix("INSERT INTO #__allquery_log (query, stamp) VALUES ('$sql', $this->requestTime)");
				$this->interface->query($sql);
			}
			return $this->_cursor;
		}
		else {
			$this->_errorNum = $this->interface->errno();
			// If error is lost connection, try reconnecting and repeating the operation
			if (2006 == $this->_errorNum OR 2013 == $this->_errorNum) {
				$this->connectToDB();
                $this->_cursor = $this->doQueryWork($timer, $sql);
				if ($this->_cursor) return $this->_cursor;
			}
			$this->_errorMsg = $this->interface->error()." SQL=$sql";
			throw new databaseException ($this->DBname, $this->_errorMsg, $this->_sql, $this->_errorNum, aliroBase::trace());
		}
	}
	
	protected function doQueryWork ($timer, $sql) {
		$cursor = $this->interface->query($sql);
        if ($cursor) {
			$this->_errorNum = 0;
			$this->_errorMsg = '';
			$stats = new stdClass;
			$stats->timer = $timer->getElapsed();
			$stats->trace = aliroBase::trace(false);
			$query = strlen($sql) < 250 ? $sql : $this->T_('LONG QUERY STARTING: ').substr($sql, 0, 120);
			$stats->sql = $query;
			self::$stats[] = $stats;
			$this->_log[] = htmlspecialchars($query).'<br />'.$timer->mark('secs for query').'<br />'.$stats->trace;
			return $cursor;
		}
		return null;
	}

	public function query_batch() {
		$this->_errorNum = 0;
		$this->_errorMsg = '';
		if ($this->interface->multiQuery($this->_sql)) {
		    do $result = $this->interface->storeResult();
		    while (0 == $this->interface->errno() AND $this->interface->nextResult());
		}
		if ($this->interface->errno()) {
			$this->_errorNum = $this->interface->errno();
			$this->_errorMsg = $this->interface->error();
			throw new databaseException ($this->DBname, $this->_errorMsg, T_('Batch query'), $this->_errorNum, aliroBase::trace());
		}
	}

	// Combined operation - takes SQL and executes it
	public function doSQL ($sql) {
		$this->setQuery($sql);
		return $this->query();
	}

	public function getNumRows ($cur=null) {
		return $this->interface->getNumRows($cur);
	}

	public function getAffectedRows () {
		return $this->interface->getAffectedRows();
	}

	// Not intended for use outside the database class framework
	public function retrieveResults ($key='', $max=0, $result_type='row') {
		$results = array();
		if (!in_array($result_type, array ('row', 'object', 'assoc'))) {
			$this->_errorMsg = sprintf($this->T_('Unexpected result type of %s in call to database'), $result_type)." SQL=$sql";
			throw new databaseException ($this->DBname, $this->_errorMsg, $this->_sql, $this->_errorNum, aliroBase::trace());
		}
		$sql_function = $this->interface->getFetchFunc().$result_type;
		$cur = $this->query();
		if ($cur) {
			while ($row = $sql_function($cur)) {
				if ($key != '') $results[(is_array($row) ? $row[$key] : $row->$key)] = $row;
				else $results[] = $row;
				if ($max AND count($results) >= $max) break;
			}
			$this->interface->freeResultSet($cur);
		}
		return $results;
	}

	public function loadResult() {
		$results = $this->retrieveResults('', 1, 'row');
		if (count($results)) return $results[0][0];
		else return null;
	}

	public function loadResultArray($numinarray = 0) {
		$results = $this->retrieveResults('', 0, 'row');
		foreach ($results as $result) $values[] = $result[$numinarray];
		return isset($values) ? $values : null;
	}

	public function loadAssocList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'assoc');
		if (count($results)) return $results;
		else return null;
	}

	// Of questionable value - not used in Aliro except for compatibility in mambofunc
	public function mosBindArrayToObject( $array, $obj, $ignore='', $prefix=NULL, $checkSlashes=true ) {
		if (!is_array($array) OR !is_object($obj)) return false;
		if ($prefix == null) $prefix = '';
		foreach (get_object_vars($obj) as $k => $v) {
			if( substr( $k, 0, 1 ) != '_' AND strpos($ignore, $k) === false) {
				if (isset($array[$prefix.$k])) {
					$obj->$k = ($checkSlashes AND get_magic_quotes_gpc()) ? $this->mosStripslashes( $array[$prefix.$k] ) : $array[$prefix.$k];
				}
			}
		}
		return true;
	}

	// Of questionable value - not used in Aliro except for compatibility in mambofunc
	public function mosStripslashes($value) {
	    if (is_string($value)) $ret = stripslashes($value);
		elseif (is_array($value)) {
	        $ret = array();
	        foreach ($value as $key=>$val) $ret[$key] = $this->mosStripslashes($val);
	    }
		else $ret = $value;
	    return $ret;
	} // mosStripSlashes

	// May be obscure to users how this will behave depending on prior setting of parameter
	public function loadObject(&$object=null) {
		if (!is_object($object)) $results = $this->retrieveResults('', 1, 'object');
		else $results = $this->retrieveResults('', 1, 'assoc');
		if (0 == count($results)) return false;
		if (!is_object($object)) $object = $results[0];
		else {
			if ($object instanceof aliroDBGeneralRow) $object->bind($results[0], '', false);
			else foreach (get_object_vars($object) as $k => $v) {
				if ($k[0] != '_' AND isset($results[0][$k])) $object->$k = $results[0][$k];
			}
		}
		return true;
	}

	public function loadObjectList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'object');
		return count($results) ? $results : null;
	}

	public function loadRow() {
		$results = $this->retrieveResults('', 1, 'row');
		return count($results) ? $results[0] : null;
	}

	public function loadRowList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'row');
		return count($results) ? $results : null;
	}

	// Deprecated in favour of allowing the system to handle errors
	public function stderr( $showSQL = false ) {
		return "DB function failed with error number $this->_errorNum"
		."<br /><font color=\"red\">$this->_errorMsg</font>"
		.($showSQL ? "<br />SQL = <pre>$this->_sql</pre>" : '');
	}

	public function insertid() {
		return $this->interface->insertid();
	}

	public function getVersion()
	{
		return $this->interface->getVersion();
	}

	/**
	* Fudge method for ADOdb compatibility???? Not used in Aliro
	*/
	public function GenID () {
		return '0';
	}

	// Usual use is to check for existence of a table - easier to use tableExists which expects #_ type table name
	// Also more efficient as tableExists uses cache
	public function getTableList() {
		$this->setQuery('SHOW tables');
		return $this->loadResultArray();
	}

	// This is probably useful - what exactly does it do?
	public function getTableCreate( $tables ) {
		$result = array();

		foreach ($tables as $tblval) {
			$this->setQuery( 'SHOW CREATE table ' . $tblval );
			$this->query();
			$result[$tblval] = $this->loadResultArray( 1 );
		}

		return $result;
	}

	// This is also potentially useful, but requires translated prefix.
	// Easier to use getAllFieldNames repeatedly - also more efficient, as uses cache
	public function getTableFields( $tables ) {
		$result = array();

		foreach ($tables as $tblval) {
			$this->setQuery('SHOW FIELDS FROM ' . $tblval);
			$fields = $this->retrieveResults ('', 0, 'object');
			foreach ($fields as $field) {
				$result[$tblval][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type );
			}
		}

		return $result;
	}
	
	public function getCount () {
		return count($this->_log);
	}

	public function getLogged () {
		$text = '<h4>'.$this->getCount().' queries executed</h4>';
	 	foreach ($this->_log as $k=>$sql) $text .= "\n".($k+1)."<br />".$sql.'<hr />';
		if (count($this->_log)) return $text;
		else return '';
	}

	protected function saveStats () {
		new aliroObjectSorter(self::$stats, 'timer');
		$n = count(self::$stats);
		if ($n > 0) {
			$median = self::$stats[intval($n/2)]->timer;
			$total = 0.0;
			foreach (self::$stats as $stat) $total += $stat->timer;
			$mean = $total/$n;
			$var = 0.0;
			foreach (self::$stats as $stat) $var += ($stat->timer - $mean) * ($stat->timer - $mean);
			$stdev = sqrt($var);
			$best = self::$stats[0]->timer;
			$worst = self::$stats[$n-1]->timer;
			$elapsed = aliro::getInstance()->getElapsed();
			$memory = memory_get_usage();
			$uri = isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') : '';
			$post = base64_encode(serialize($_POST));
			$ip = aliroSession::getSession()->getIP();
			$database = aliroCoreDatabase::getInstance();
			$database->doSQL("INSERT INTO #__query_stats (count, mean, median, stdev, best, worst, total, elapsed, memory, uri, post, ip) VALUES ($n, '$mean', '$median', '$stdev', '$best', '$worst', '$total', '$elapsed', '$memory', '$uri', '$post', '$ip')");
			$queryid = $this->insertid();
			for ($i = $n-1; $i >= 0; $i--) {
				if (0.5 < self::$stats[$i]->timer) {
					$stat = self::$stats[$i];
					$querytext = $database->getEscaped($stat->sql);
					$tracetext = $this->getEscaped($stat->trace);
					$database->doSQL("INSERT INTO #__query_slow (queryid, time, trace, querytext) VALUES ($queryid, '$stat->timer', '$tracetext', '$querytext')");
				}
				else break;
			}
			if (42 == mt_rand(1,100)) {
				$database->doSQL("DELETE LOW_PRIORITY FROM #__query_stats WHERE stamp < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
				$database->doSQL("OPTIMIZE TABLE #__query_stats"); 
				$database->doSQL("DELETE LOW_PRIORITY FROM #__query_slow WHERE queryid NOT IN (SELECT id FROM #__query_stats)");
				$database->doSQL("OPTIMIZE TABLE #__query_slow"); 
			}
		}
		self::$stats = array();
	}
}

abstract class aliroExtendedDatabase {
	protected $DBInfo = null;
	protected $cache = null;
	protected $database = null;

	protected function __construct( $host, $user, $pass, $db, $table_prefix, $return_on_error=false ) {
		$this->database = new aliroBasicDatabase ($host, $user, $pass, $db, $table_prefix, $return_on_error);
		$this->cache = new aliroSimpleCache(get_class($this));
		$this->DBInfo = $this->cache->get($host.$db.$user.$table_prefix);
		if (!$this->DBInfo) $this->emptyCache();
	}

	public function __call ($method, $args) {
		return call_user_func_array(array($this->database, $method), $args);
	}
	
	public function loadObject (&$object=null) {
		$result = $this->database->loadObject($object);
		if (true === $result) return $result;
		if (is_object($result)) {
			if ($object instanceof aliroDBGeneralRow) $object->bind($result, '', false);
			else foreach (get_object_vars($object) as $k => $v) {
				if ($k[0] != '_' AND isset($result->$k)) $object->$k = $result->$k;
			}
			return true;
		}
		return false;
	}

	public function clearCache () {
		$this->cache->clean();
		$this->emptyCache();
	}

	protected function emptyCache () {
		$this->DBInfo = new stdClass();
		$this->DBInfo->DBTables = array();
		$this->DBInfo->DBFields = array();
		$this->DBInfo->DBFieldsByName = array();
		$this->getTableInfo();
	}

	// Combined operation - takes SQL and executes it
	public function doSQL ($sql) {
		$this->database->setQuery($sql);
		return $this->database->query();
	}

	// Combined operation - as above - and returns an array of objects of the specified class
	public function doSQLget ($sql, $classname='stdClass', $key='', $max=0) {
		$this->database->setQuery($sql);
		$rows = $this->retrieveResults ($key, $max, 'object');
		if ('stdClass' == $classname) return $rows;
		foreach ($rows as $sub=>$row) {
			$next = new $classname();
			foreach (get_object_vars($row) as $field=>$value) $next->$field = $value;
			$result[$sub] = $next;
		}
		unset($rows);
		return isset($result) ? $result : array();
	}

	protected function retrieveResults ($key='', $max=0, $result_type='row') {
		return $this->database->retrieveResults($key, $max, $result_type);
	}

	protected function getTableInfo () {
		if (count($this->DBInfo->DBTables) == 0) {
			$this->database->setQuery ("SHOW TABLES");
            $results = $this->database->loadResultArray();
			if ($results) foreach ($results as $result) $this->DBInfo->DBTables[] = $this->restoreOnePrefix($result);
			$this->saveCache();
		}
	}

	protected function restoreOnePrefix ($tablename) {
		return $this->database->restoreOnePrefix($tablename);
	}

	protected function saveCache () {
		$this->cache->save($this->DBInfo);
	}

	protected function storeFields ($tablename) {
		if ($this->tableExists($tablename) AND !isset($this->DBInfo->DBFields[$tablename])) {
			$this->DBInfo->DBFields[$tablename] = $this->doSQLget("SHOW FIELDS FROM `$tablename`");
			$this->DBInfo->DBFieldsByName[$tablename] = array();
			foreach ($this->DBInfo->DBFields[$tablename] as $field) $this->DBInfo->DBFieldsByName[$tablename][$field->Field] = $field;
			$this->saveCache();
		}
	}

	public function getAllFieldInfo ($tablename) {
		$this->storeFields($tablename);
		return isset($this->DBInfo->DBFields[$tablename]) ? $this->DBInfo->DBFields[$tablename] : array();
	}

	public function getAllFieldNames ($tablename) {
		$this->storeFields($tablename);
		return isset($this->DBInfo->DBFieldsByName[$tablename]) ? array_keys($this->DBInfo->DBFieldsByName[$tablename]) : array();
	}

	public function getShortFieldNames ($tablename) {
		$fieldinfo = $this->getAllFieldInfo($tablename);
		foreach ($fieldinfo as $info) {
			if (false === strpos($info->Type, 'blob') AND false === strpos($info->Type, 'text')) {
				$short[] = $info->Field;
			}
		}
		return isset($short) ? $short : array();
	}

	public function getIndexNames ($tablename) {
		if ($this->tableExists($tablename)) {
			$indexes = $this->doSQLget("SHOW INDEXES FROM `$tablename`");
			foreach ($indexes as $index) $result[] = $index->Key_name;
		}
		return isset($result) ? $result : array();
	}

	public function getShortRecords ($tablename, $condition) {
		$fields = $this->getShortFieldNames($tablename);
		if (empty($fields)) return null;
		$fieldlist = implode(',', $fields);
		return $this->doSQLget("SELECT $fieldlist FROM $tablename $condition");
	}

	public function addFieldIfMissing ($tablename, $fieldname, $fieldspec, $alterIfPresent=false) {
		if (in_array($fieldname, $this->getAllFieldNames($tablename))) {
			if ($alterIfPresent) return $this->alterField($tablename, $fieldname, $fieldspec);
			return false;
		}
		if ($this->tableExists($tablename)) {
			$this->doSQL("ALTER TABLE `$tablename` ADD `$fieldname` ".$fieldspec);
			$this->clearCache();
		}
		return true;
	}

	public function dropFieldIfPresent ($tablename, $fieldname) {
		if (!in_array($fieldname, $this->getAllFieldNames($tablename))) return false;
		$this->doSQL("ALTER TABLE $tablename DROP COLUMN `$fieldname`");
		$this->clearCache();
		return true;
	}

	public function alterField ($tablename, $fieldname, $fieldspec, $newfieldname='') {
		if (!in_array($fieldname, $this->getAllFieldNames($tablename))) return false;
		if (!$newfieldname) $newfieldname = $fieldname;
		$this->doSQL("ALTER TABLE $tablename CHANGE COLUMN `$fieldname` `$newfieldname` ".$fieldspec);
		$this->clearCache();
		return true;
	}

	public function getFieldInfo ($tablename, $fieldname) {
		$this->storeFields($tablename);
		return isset($this->DBInfo->DBFieldsByName[$tablename][$fieldname]) ? $this->DBInfo->DBFieldsByName[$tablename][$fieldname] : null;
	}

	// Expects parameter to be of the form #__name_of_table, so no need to look for DB prefix
	public function tableExists ($tablename) {
		return in_array($tablename, $this->DBInfo->DBTables);
	}

	public function insertObject ($table, $object, $keyName=NULL) {
		$query = $this->buildInsertFields($table, $object);
		$result = $query ? $this->doSQL($query) : false;
		if ($result) {
			// insertid() is only meaningful if non-zero
			$autoinc = $this->insertid();
			if ($autoinc AND $keyName AND !is_array($keyName)) $object->$keyName = $autoinc;
		}
		return $result;
	}

	protected function buildInsertFields ($table, $object, $ignore=false) {
		$dbfields = $this->getAllFieldInfo($table);
		foreach ($dbfields as $field) {
			$name = $field->Field;
			$unsuitable = (!isset($object->$name) OR is_array($object->$name) OR is_object($object->$name)) ? true : false;
			$isverylong = (false !== strpos($field->Type, 'text') OR false !== strpos($field->Type, 'blob')) ? true : false;
			if (!$isverylong AND $unsuitable) continue;
			$fields[] = "`$name`";
			$values[] = $unsuitable ? "''" : $this->setFieldValue($object->$name, $field->Type);
		}
		if (isset($fields)) {
			return $this->makeInsertSQL ($table, implode( ",", $fields ), implode( ",", $values ), $ignore);
		}
		else {
			trigger_error (sprintf($this->T_('Insert into table %s but no fields'), $this->tableName));
			$this->trace();
			return false;
		}
	}

	protected function makeInsertSQL ($table, $fields, $values, $ignore=false) {
		$sqlstart = $ignore ? 'INSERT IGNORE INTO' : 'INSERT INTO';
		return "$sqlstart $table ($fields) VALUES ($values)";
	}

	public function updateObject ($table, $object, $keyName, $updateNulls=true) {
		$dbfields = $this->getAllFieldInfo($table);
		foreach ($dbfields as $field) {
			$name = $field->Field;
			if (!isset($object->$name) OR is_array($object->$name) OR is_object($object->$name)) {
				if ($updateNulls) $value = "''";
				else continue;
			}
			else $value = $this->setFieldValue($object->$name, $field->Type);
			$setter = "`$name` = $value";
			if (is_array($keyName) AND in_array($name, $keyName)) $where[] = $setter;
			elseif (!is_array($keyName) AND $name == $keyName) $where[] = $setter;
			else $setters[] = $setter;
		}
		if (!isset($where)) {
			trigger_error (sprintf($this->T_('Update table %s but no key fields'), $table));
			return false;
		}
		if (isset($setters)) return $this->doUpdate ($table, implode (', ', $setters), implode (' AND ' , $where));
		return true;
	}

	// Note that this will not work when aliroExtendedDatabase is used with MiaCMS/Mambo/Joomla
	public function setFieldValue ($data, $type='varchar') {
		return $this->database->setFieldValue($data, $type);
	}

	protected function doUpdate ($table, $setters, $conditions) {
		return $this->doSQL("UPDATE $table SET $setters WHERE $conditions");
	}

	public function insertOrUpdateObject ($table, $object, $keyName, $updateNulls=true) {
		$query = $this->buildInsertFields($table, $object).' ON DUPLICATE KEY UPDATE ';
		$dbfields = $this->getAllFieldInfo($table);
		foreach ($dbfields as $field) {
			$name = $field->Field;
			if (is_array($keyName) AND in_array($name, $keyName)) continue;
			if (!is_array($keyName) AND $name == $keyName) continue;
			if (!isset($object->$name) OR is_array($object->$name) OR is_object($object->$name)) {
				if ($updateNulls) $value = "''";
				else continue;
			}
			else $value = $this->setFieldValue($object->$name, $field->Type);
			$setters[] = "`$name` = $value";
		}
		$query .= implode(', ', $setters);
		$this->doSQL($query);
	}

	// If the insert fails, the problem is ignored - use affected rows to find what happened
	public function insertObjectSafely ($table, $object) {
		$this->doSQL($this->buildInsertFields($table, $object, true));
	}

	protected function trace () {
		echo aliroBase::trace();
	}

	protected function T_($string) {
		return function_exists('T_') ? T_($string) : $string;
	}
}

// Provided for backwards compatibility
class database extends aliroBasicDatabase {

}

// Primarily used during installation before the following
// classes can be invoked
class aliroDatabaseHandler extends aliroBasicDatabase {

	public static function validateCredentials ($host, $user, $pass, $db) {
		$interface = aliroDatabaseHandler::getInterface($db);
		if (!$interface) return _ALIRO_DB_NO_INTERFACE;
		return ($interface->connect($host, $user, $pass, $db)) ? 0 : _ALIRO_DB_CONNECT_FAILED;
	}

	public static function getInterface ($dbname) {
		if (function_exists( 'mysqli_connect' )) return new mysqliInterface($dbname);
		if (function_exists( 'mysql_connect' )) return new mysqlInterface($dbname);
		return  null;
	}
}

// The general database for an Aliro system
class aliroDatabase extends aliroExtendedDatabase {
	protected static $instance = null;

	protected function __construct () {
		$credentials = aliroCore::getConfigData('credentials.php');
		parent::__construct ($credentials['dbhost'], $credentials['dbusername'], $credentials['dbpassword'], $credentials['dbname'], $credentials['dbprefix']);
		if (aliro::getInstance()->installed) aliroCore::set('dbprefix', $credentials['dbprefix']);
	}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self());
	}

	// Not intended for general use - public only to allow access by system upgrader
	public function DBUpgrade () {
		$sql = file_get_contents(_ALIRO_ADMIN_CLASS_BASE.'/sql/aliro_general.sql');
		$this->setQuery($sql);
		$this->query_batch();
		$this->clearCache();
		$this->addFieldIfMissing('#__remosef_uri', 'marker', "tinyint(4) NOT NULL default '0'");
		if ($this->tableExists('#__remosef_uri') AND $this->addFieldIfMissing('#__remosef_uri', 'sef_crc', 'int(11) UNSIGNED NOT NULL default 0 AFTER `id`')) {
			$this->doSQL("ALTER TABLE `#__remosef_uri` DROP INDEX `sef`");
			$this->doSQL("UPDATE #__remosef_uri SET sef_crc = CRC32(sef)");
			$this->doSQL("ALTER TABLE `#__remosef_uri` ADD INDEX (`sef_crc`) ");
		}
		if ($this->tableExists('#__remosef_uri') AND $this->addFieldIfMissing('#__remosef_uri', 'uri_crc', 'int(11) UNSIGNED NOT NULL default 0 AFTER `id`')) {
			$this->doSQL("ALTER TABLE `#__remosef_uri` DROP INDEX `uri`");
			$this->doSQL("UPDATE #__remosef_uri SET uri_crc = CRC32(uri)");
			$this->doSQL("ALTER TABLE `#__remosef_uri` ADD INDEX (`uri_crc`) ");
		}
		if ($this->tableExists('#__remosef_metadata') AND $this->addFieldIfMissing('#__remosef_metadata', 'uri_crc', 'int(11) UNSIGNED NOT NULL default 0 AFTER `id`')) {
			$this->doSQL("ALTER TABLE `#__remosef_metadata` DROP INDEX `finduri`");
			$this->doSQL("UPDATE #__remosef_metadata SET uri_crc = CRC32(uri)");
			$this->doSQL("ALTER TABLE `#__remosef_metadata` ADD INDEX (`uri_crc`) ");
		}
		if ($this->tableExists('#__remosef_uri') AND $this->addFieldIfMissing('#__remosef_uri', 'shortterm', 'tinyint(4) UNSIGNED NOT NULL default 0 AFTER `id`')) {
			$this->doSQL("ALTER TABLE `#__remosef_uri` ADD INDEX (`shortterm`) ");
		}
		$this->addFieldIfMissing('#__remosef_uri', 'ipaddress', "VARCHAR(15) NOT NULL default '' AFTER `sef_crc`");
		$this->addFieldIfMissing('#__remosef_config', 'flags', 'tinyint(3) UNSIGNED NOT NULL default 0 AFTER `id`');
		$this->addFieldIfMissing('#__users', 'jobtitle', "VARCHAR(100) NOT NULL default '' AFTER `email`");
		$this->addFieldIfMissing('#__users', 'timezone', "VARCHAR(255) NOT NULL default '' AFTER `jobtitle`");
		$this->addFieldIfMissing('#__users', 'location', "VARCHAR(255) NOT NULL default '' AFTER `timezone`");
		$this->addFieldIfMissing('#__users', 'phone', "VARCHAR(100) NOT NULL default '' AFTER `location`");
		$this->addFieldIfMissing('#__users', 'website', "VARCHAR(255) NOT NULL default '' AFTER `location`");
		$this->addFieldIfMissing('#__users', 'avatype', "VARCHAR(4) NOT NULL default '' AFTER `lastvisitDate`");
		$this->addFieldIfMissing('#__users', 'avatar', "BLOB NOT NULL AFTER `params`");
		$this->addFieldIfMissing('#__users', 'special', "VARCHAR(255) NOT NULL default '' AFTER `avatar`");
		$this->addFieldIfMissing('#__users', 'ipaddress', "VARCHAR(15) NOT NULL default '' AFTER `usertype`");
		$this->addFieldIfMissing('#__users', 'countrycode', "VARCHAR(15) NOT NULL default '' AFTER `ipaddress`");
		
		if (in_array('sequence', $this->getIndexNames('#__remosef_config'))) $this->doSQL("ALTER TABLE `#__remosef_config` DROP INDEX `sequence`");

		$this->alterField('#__remosef_config', 'name', "TEXT NOT NULL");
		$this->alterField('#__remosef_config', 'modified', "TEXT NOT NULL");
		
		$this->clearCache();
	}
}

// For backwards compatibility
class mamboDatabase extends aliroDatabase {
	// Just an alias really
}

// Similar to aliroDatabase but with any conflicting methods overriden
class joomlaDatabase extends aliroDatabase {
	protected static $instance = null;
	
	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self());
	}
	
	public function loadObject (&$object=null) {
		$object = null;
		$this->loadObject($object);
		return $object;
	}
}

// The core database for an Aliro system - applications should not normally need to use it
class aliroCoreDatabase extends aliroExtendedDatabase {
	protected static $instance = null;

	protected function __construct () {
		$credentials = aliroCore::getConfigData('corecredentials.php');
		parent::__construct ($credentials['dbhost'], $credentials['dbusername'], $credentials['dbpassword'], $credentials['dbname'], $credentials['dbprefix']);
	}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self());
	}
	
	public function changeDBContents () {
		$this->doSQL("UPDATE #__extensions SET xmlfile = SUBSTRING(xmlfile,15) WHERE '/administrator' = SUBSTRING(xmlfile,1,14)");
		$this->doSQL("UPDATE #__admin_menu SET xmlfile = SUBSTRING(xmlfile,15) WHERE '/administrator' = SUBSTRING(xmlfile,1,14)");
		$this->doSQL("UPDATE #__menu SET xmlfile = SUBSTRING(xmlfile,15) WHERE '/administrator' = SUBSTRING(xmlfile,1,14)");
	}
	
	// Not intended for general use - public only to allow access by system upgrader
	public function DBUpgrade () {
		$sql = file_get_contents(_ALIRO_ADMIN_CLASS_BASE.'/sql/aliro_core.sql');
		$this->setQuery($sql);
		$this->query_batch();
		$this->clearCache();
		if (!$this->tableExists('#__menutypes')) {
			$this->doSQL("CREATE TABLE IF NOT EXISTS `#__menutype` ("
			." `id` int(11) NOT NULL auto_increment,"
			." `ordering` int(11) NOT NULL default '0',"
			." `type` varchar(25) NOT NULL default '',"
			." `name` varchar(255) NOT NULL default '',"
			." PRIMARY KEY  (`id`)"
			." ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
			$this->doSQL("INSERT INTO `#__menutype` (ordering, type, name) SELECT DISTINCT IF(menutype = 'mainmenu', 10, 20) AS ordering, menutype, menutype FROM `#__menu`");
		}
		else $this->doSQL("DELETE FROM #__menutype WHERE type NOT IN (SELECT DISTINCT menutype FROM #__menu)");
		$this->doSQL("DELETE FROM #__admin_menu WHERE link LIKE 'index.php?core=cor_menu%'");
		$this->setQuery("SELECT id FROM #__admin_menu WHERE link = 'index.php?placeholder=manage_site'");
		$sitemanager = $this->loadResult();
		$this->doSQL("INSERT INTO #__admin_menu (name, link, type, published, parent, checked_out_time) VALUES('Menus', 'index.php?core=cor_menus&act=type', 'core', 1, $sitemanager, '{$this->dateNow()}')");
		$menutop = $this->insertid();
		$this->doSQL("INSERT INTO #__admin_menu (name, link, type, published, parent, checked_out_time) SELECT name, CONCAT('index.php?core=cor_menus&task=list&menutype=', type) AS link, 'core' AS type, 1 AS published, $menutop, '{$this->dateNow()}' AS checked_out_time FROM #__menutype");
		$this->addFieldIfMissing('#__classmap', 'extends', "varchar(255) NOT NULL default '' AFTER `classname`");
		$this->addFieldIfMissing('#__extensions', 'inner', "tinyint(3) unsigned NOT NULL default '0' AFTER `default_template`");
		$this->addFieldIfMissing('#__extensions', 'package', "varchar(255) NOT NULL default '' AFTER `type`");
		if ($this->addFieldIfMissing('#__extensions', 'application', "varchar(100) NOT NULL default '' AFTER `package`")) {
			$this->doSQL("UPDATE #__extensions SET application = formalname");
			$clearHandlers = 1;
		}
		foreach (array('#__menu', '#__extensions', '#__admin_menu') as $tablename) {
			if ($this->addFieldIfMissing($tablename, 'parmspec', "text NOT NULL AFTER `xmlfile`")) {
				$this->makeParmSpecs($tablename);
				$clearHandlers = 1;
			}
		}
		if (!empty($clearHandlers)) {
			aliroSingletonObjectCache::getInstance()->delete('aliroExtensionHandler', 'aliroMenuHandler', 'aliroAdminMenuHandler');
		}
		
		$this->addFieldIfMissing('#__menu', 'home', 'tinyint(3) UNSIGNED NOT NULL default 0 AFTER `published`');
		
		$this->addFieldIfMissing('#__urilinks', 'notemplate', 'tinyint(3) UNSIGNED NOT NULL default 1 AFTER `published`');
		$this->addFieldIfMissing('#__urilinks', 'nohtml', 'tinyint(3) UNSIGNED NOT NULL default 1 AFTER `notemplate`');
		$this->addFieldIfMissing('#__urilinks', 'uri_crc', 'int(10) UNSIGNED NOT NULL default 1 AFTER `nohtml`');

		$this->dropFieldIfPresent('#__session_data', 'timestamp');
		if ($this->tableExists('#__session_data') AND $this->addFieldIfMissing('#__session_data', 'session_id_crc', 'int(11) UNSIGNED NOT NULL default 0 AFTER `session_id`')) {
			$this->doSQL("ALTER TABLE `#__session_data` DROP PRIMARY KEY");
			$this->doSQL("UPDATE #__session_data SET session_id_crc = CRC32(session_id)");
			$this->doSQL("ALTER TABLE `#__session_data` ADD INDEX (`session_id_crc`) ");
		}
		$this->addFieldIfMissing('#__session_data', 'marker', 'int(11) NOT NULL default 0 AFTER `session_id`');

		$this->addFieldIfMissing('#__modules', 'repeats', "tinyint(3) unsigned NOT NULL default 0 AFTER `ordering`");
		$this->addFieldIfMissing('#__modules', 'exclude', "tinyint(3) unsigned NOT NULL default 0 AFTER `repeats`");
		$this->addFieldIfMissing('#__modules', 'incountry', "varchar(255) NOT NULL default '' AFTER `position`");
		$this->addFieldIfMissing('#__modules', 'excountry', "varchar(255) NOT NULL default '' AFTER `incountry`");

		$this->addFieldIfMissing('#__query_stats', 'post', "text NOT NULL AFTER `uri`");
		
		$this->addFieldIfMissing('#__query_stats', 'ip', "varchar (15) NOT NULL default '' AFTER `post`");
		$this->addFieldIfMissing('#__error_404', 'ip', "varchar (15) NOT NULL default '' AFTER `referer`");
		$this->addFieldIfMissing('#__error_404', 'errortype', "varchar (5) NOT NULL default '' AFTER `ip`");
		$this->addFieldIfMissing('#__error_log', 'ip', "varchar (15) NOT NULL default '' AFTER `referer`");
		$this->alterField('#__error_log', 'dbmessage', "TEXT NOT NULL");
		$this->addFieldIfMissing('#__mail_log', 'ip', "varchar (15) NOT NULL default '' AFTER `recipient`");
		$this->addFieldIfMissing('#__orphan_data', 'stamp', "timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP AFTER `orphandata`");
		
		$this->dropFieldIfPresent('#__session', 'usertype');
		$this->dropFieldIfPresent('#__session', 'httphost');
		$this->dropFieldIfPresent('#__session', 'servername');
		$this->dropFieldIfPresent('#__session', 'username');
		$this->alterField('#__session', 'session_id', "char(32) NOT NULL");

		$this->alterField('#__session_data', 'session_id', "char(32) NOT NULL");
		
		$this->clearCache();
	}
	
	protected function makeParmSpecs ($tablename) {
		$rows = $this->doSQLget("SELECT id, xmlfile FROM $tablename");
		clearstatcache();
		foreach ($rows as $row) {
			if ($row->xmlfile AND file_exists(_ALIRO_CLASS_BASE.$row->xmlfile)) {
				$xmlobject = new aliroXML();
				$xmlobject->loadFile(_ALIRO_CLASS_BASE.$row->xmlfile);
				$parmlist = $xmlobject->getXML('params');
				$parmspec = aliroXMLParams::makeParmSpecString($parmlist);
				$this->doSQL("UPDATE $tablename SET parmspec = '$parmspec' WHERE id = $row->id");
			}
		}
	}
	
}
