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
 * aliroErrorRecorder provides a simple way to log errors to the database.  It
 * will accept a short message, a long message, and optionally an exception as
 * parameters.  It derives for itself the POST and GET data, and also a trace
 * of execution.  The whole is stored as a database record, in a table which
 * is pruned to keep it to a maximum of 7 days so it will not grow too large.
 *
 */

final class aliroErrorRecorder extends aliroDatabaseRow  {
    protected static $instance = null;
	protected $DBclass = 'aliroCoreDatabase';
	protected $tableName = '#__error_log';
	protected $rowKey = 'id';

	public static function getInstance ($request=null) {
	    return (null == self::$instance) ? (self::$instance = new self()) : self::$instance;
	}

	public function PHPerror ($errno, $errstr, $errfile, $errline, $errcontext) {
		if (!($errno & error_reporting())) return;
	    $rawmessage = function_exists('T_') ? T_('PHP Error %s: %s in %s at line %s') : 'PHP Error %s: %s in %s at line %s';
	    $message = sprintf($rawmessage, $errno, $errstr, $errfile, $errline);
        $lmessage = $message;
        if (is_array($errcontext)) {
            foreach ($errcontext as $key=>$value) if (!is_object($value) AND !(is_array($value))) $lmessage .= "; $key=$value";
        }
        $errorkey = "PHP/$errno/$errfile/$errline/$errstr";
	    $this->recordError($message, $errorkey, $lmessage);
	    aliroRequest::getInstance()->setErrorMessage(T_('A PHP error has been recorded in the log'), _ALIRO_ERROR_WARN);
	    if ($errno & (E_USER_ERROR|E_COMPILE_ERROR|E_CORE_ERROR|E_ERROR)) die (T_('Serious PHP error - processing halted - see error log for details'));
	}

	public function recordError ($smessage, $errorkey, $lmessage='', $exception=null) {
	    $this->id = 0;
		$this->timestamp = date ('Y-m-d H:i:s');
		$this->ip = aliroSession::getSession()->getIP();
		$this->smessage = substr($smessage, 0, 250);
		$this->lmessage = $lmessage ? $lmessage : $smessage;
		$this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$database = aliroCoreDatabase::getInstance();
		$this->errorkey = $database->getEscaped($errorkey);
		$this->get = @$_SERVER['REQUEST_URI'];
		$this->post = base64_encode(serialize($_POST));
		$this->trace = aliroBase::trace();
		if ($exception instanceof databaseException) {
			$this->dbname = $exception->dbname;
			$this->sql = $exception->sql;
			$this->dberror = $exception->getCode();
			$this->dbmessage = $exception->getMessage();
			$this->dbtrace = $exception->dbtrace;
		}
		// Must set text field, has no default
		else $this->dbname = $this->sql = $this->dberror = $this->dbmessage = '';
		$database->setQuery("SELECT id FROM #__error_log WHERE errorkey = '$this->errorkey'");
		$id = $database->loadResult();
		if (!$id) $this->store();
		else $database->doSQL("UPDATE #__error_log SET timestamp = NOW() WHERE id = $id");
		// code to prune error log - limit to max items, max days
		$database = call_user_func(array($this->DBclass, 'getInstance'));
		$database->doSQL("DELETE LOW_PRIORITY FROM $this->tableName WHERE timestamp < SUBDATE(NOW(), INTERVAL 7 DAY)");
	}
}