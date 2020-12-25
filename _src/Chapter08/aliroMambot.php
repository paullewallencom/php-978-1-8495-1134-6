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
 * aliroMambot is the class for mambot objects - the descriptors for Aliro plugins.
 *
 * aliroMambotHandler manages all the descriptors, operating as a cached singleton.
 * It provides some basic methods to support the installer, can return an array of
 * the mambots that will respond to a particular trigger (used, for example, to find
 * all the editors) and most significantly implements the trigger and call methods
 * that actually invoke mambots.  The method loadBotGroup is provided for compatibility
 * but does nothing since all mambots in Aliro are loaded using the smart class
 * loader when they are triggered - there is no need to call loadBotGroup.
 *
 */

class aliroMambot extends aliroDatabaseRow {
	protected $DBclass = 'aliroCoreDatabase';
	protected $tableName = '#__mambots';
	protected $rowKey = 'id';
	protected $handler = 'aliroMambotHandler';
	protected $formalfield = 'element';

}

// Provided only for backwards compatibility
class mosMambotHandler {

	public static function getInstance () {
		return aliroMambotHandler::getInstance();
	}
}

class aliroMambotHandler extends aliroCommonExtHandler  {

    protected static $instance = __CLASS__;

    private static $defaults = array (
    'onIniEditor' => 'bot_nulleditor',
    'onGetEditorContents' => 'bot_nulleditor',
    'onEditorArea' => 'bot_nulleditor'
    );

	private static $internalBots = array (
		array('element' => 'bot_example', 'class' => 'class,method', 'triggers' => 'triggers')
	);

	private static $monitors = array (
	);

    private $events=array();
    private $bot_objects = array();
	private $botsByName = array();

    protected $extensiondir = '/mambots/';

    protected function __construct() {
        $database = aliroCoreDatabase::getInstance();
        $this->botsByName = $database->doSQLget( "SELECT element, class, triggers, published, params, 0 AS isdefault FROM #__mambots ORDER BY ordering", 'stdClass', 'element');
        foreach (self::$defaults as $trigger=>$default) {
        	$defobj = new stdClass;
        	$defobj->class = $defobj->element = $default;
        	$defobj->triggers = $trigger;
        	$defobj->isdefault = 1;
        	$this->botsByName[$default] = $defobj;
        }
		foreach (self::$internalBots as $internal) {
			$intobj = new stdClass();
			$intobj->element = $internal['element'];
			$intobj->class = $internal['class'];
			$intobj->triggers = $internal['triggers'];
			$intobj->isdefault = 0;
			$this->botsByName[$intobj->element] = $intobj;
		}
        foreach ($this->botsByName as $element=>$bot) {
        	$triggers = explode (',', $bot->triggers);
        	foreach ($triggers as $trigger) $this->events[trim($trigger)][] = $element;
        }
    }

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = parent::getCachedSingleton(self::$instance));
	}

	private function getByName ($formalname) {
		return isset($this->botsByName[$formalname]) ? $this->botsByName[$formalname] : null;
	}

	public function isPluginPresent ($formalname, $andPublished=false) {
		$bot = $this->getByName($formalname);
		if (!is_object($bot)) return false;
		return $bot->published ? true : ($andPublished ? false : true);
	}

	public function getNamesForTrigger ($trigger) {
		return isset($this->events[$trigger]) ? $this->events[$trigger] : array();
	}

	// Not currently used in Aliro or its common extensions - provided for compatibility?
	public function getMambotsForTrigger ($trigger) {
		return isset($this->events[$trigger]) ? array_map(array($this, 'getByName'), $this->events[$trigger]) : array();
	}

    public function loadBotGroup( $group ) {
    	// Only required for backward compatibility
    	return true;
    }

    // The bulk of the work of running plugins is done here
    // The main method for invoking Aliro plugins
    public function trigger( $event, $args=null, $doUnpublished=false, $maxbot=0 ) {
        if ($args === null) $args = array();
        elseif (!is_array($args)) $args = array($args);
        $result = array();
        $botcount = 0;
        if (isset( $this->events[$event] )) foreach ($this->events[$event] as $element) {
           	$bot = $this->botsByName[$element];
           	if ($bot->isdefault) {
           		if (empty($defaultbotkey)) $defaultbotkey = $element;
           	}
           	else {
	           	$botparams = new aliroParameters($bot->params);
	           	if ($doUnpublished OR $bot->published) {
	           		$result[] = $this->runOneBot($element, $args, $event, $botparams, $bot->published);
	           		$botcount ++;
	           		if ($maxbot AND $botcount >= $maxbot) break;
	           	}
           	}
        }
        if (0 == $botcount AND isset($defaultbotkey)) $result[] = $this->runOneBot($defaultbotkey, $args, $event, '', '1');
		if (isset(self::$monitors[$event])) $this->passToMonitor(self::$monitors[$event], $args, $result);
        return $result;
    }


    public function triggerByName ($event, $formalname, $args=null, $doUnpublished=false) {
		$bot = $this->getByName($formalname);
		if (is_object($bot) AND in_array($bot->element, $this->events[$event])) {
           	$botparams = new aliroParameters($bot->params);
           	if ($doUnpublished OR $bot->published) {
           		$result = $this->runOneBot($bot->element, $args, $event, $botparams, $bot->published);
				if (isset(self::$monitors[$event])) $this->passToMonitor(self::$monitors[$event], $args, array($result));
				return $result;
           	}
		}
		if (isset(self::$monitors[$event])) $this->passToMonitor(self::$monitors[$event], $args, array());
		return null;
    }

    private function runOneBot ($botelement, $args, $event, $botparams, $published) {
   		$botinfo = $this->botsByName[$botelement];
		$botcaller = explode(',', $botinfo->class);
   		$classname = trim($botcaller[0]);
		$method = (1 < count($botcaller)) ? trim($botcaller[1]) : $event;
		try {
		    $refmethod = new ReflectionMethod("$classname::$method");
		    if ( $refmethod->isStatic() )
		    {
		        // verified that class and method are defined AND method is static
		    	array_unshift($args, $event, $botparams, $published);
				return call_user_func_array(array($classname, $method), $args);
		    }
		}
		catch ( ReflectionException $e ) {
		    //  method does not exist
		    $method = 'perform';
	    	array_unshift($args, $event, $botparams, $published);
		}
       	if (isset($this->bot_objects[$botelement])) $botobject = $this->bot_objects[$botelement];
       	else {
   			$config = array('params' => $botparams, 'published' => $published, 'name' => (isset($botinfo->element) ? $botinfo->element : $botinfo->class));
       		$botobject = $this->bot_objects[$botelement] = new $classname($this, $config);
       	}
    	if (method_exists($botobject, $method)) return call_user_func_array(array($botobject, $method), $args);
		trigger_error(sprintf(T_('Could not find a valid method to call a plugin %s, tried %s'), $classname, $method));
		return null;
    }

	private function passToMonitor ($monitor, $args, $results) {
		$parts = explode(',', $monitor);
		if (2 == count($parts)) {
			$method = trim($parts[1]);
			$object = aliroRequest::getInstance()->getClassObject(trim($parts[0]));
			if (is_object($object) AND method_exists($object, $method)) $object->$method($args, $results);
		}
	}

    public function countBots ($event) {
    	return isset($this->events[$event]) ? count($this->events[$event]) : 0;
    }

    // Trigger function for activating just one bot - provided for convenience in calling
    public function triggerOnce ($event, $args=null, $doUnpublished=false) {
    	$results = $this->trigger ($event, $args, $doUnpublished, 1);
		return count($results) ? $results[0] : null;
    }

	// Alternative way to invoke a plugin - doesn't appear to be used now
    public function call( $event ) {
        $args = func_get_args();
        array_shift($args);
        $result = $this->trigger($event, $args);
        if (isset($result[0])) return $result[0];
        return null;
    }
}