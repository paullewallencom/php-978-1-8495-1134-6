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
 * These classes are the optional basis for component (or to some extent
 * other add-ons) construction.  They are used extensively by Aliro itself.
 *
 * aliroFriendlyBase simply provides for any class that uses it as a base
 * to have properties and methods of general utility, such as access to
 * configuration data.
 *
 * aliroComponentManager is the abstract base class for manager classes
 * for components.
 *
 * aliroComponentUserManager is the class that provides initial logic to
 * handle the startup of a component on the user side.  It decides what
 * class and method to call.
 *
 * aliroComponentControllers is the base class for component logic, to be
 * called under the control of the manager class.  So the manager decides
 * what function has been requested, and from that uses a simple scheme
 * to derive the name of the controller class and method to handle the
 * request.  The actual code of the component inherits from this class.
 *
 */

abstract class aliroFriendlyBase {

	protected function getTableInfo ($tablename) {
		$database = call_user_func(array($this->DBname, 'getInstance'));
		return $database->getAllFieldInfo($tablename);
	}

	// protected function __call
	public function __call ($method, $args) {
		return call_user_func_array(array(aliroRequest::getInstance(), $method), $args);
	}

	// protected function __get
	public function __get ($property) {
		if ('option' == $property) return aliroRequest::getInstance()->getOption();
		$info = criticalInfo::getInstance();
		if (isset($info->$property)) return $info->$property;
		trigger_error(sprintf(T_('Invalid criticalInfo property %s requested through aliroFriendlyBase'), $property));
	}

	protected final function getCfg ($property) {
		return aliroCore::getInstance()->getCfg($property);
	}

	protected final function getParam ($array, $key, $default=null, $mask=0) {
		return aliroRequest::getInstance()->getParam ($array, $key, $default, $mask);
	}

	protected final function getStickyParam ($array, $key, $default=null, $mask=0) {
		return aliroRequest::getInstance()->getStickyParam ($array, $key, $default, $mask);
	}

	protected final function redirect ($url, $message='', $severity=_ALIRO_ERROR_INFORM) {
		aliroRequest::getInstance()->redirect($url, $message, $severity);
	}

	protected final function getUser () {
		$user = aliroUser::getInstance();
		return $user;
	}

	protected function formatDate ($time=null, $format=null) {
		return aliroLanguage::getInstance()->formatDate($time, $format);
	}

}

/**
* Component common base class for both user and admin sides
*/

abstract class aliroComponentManager extends aliroFriendlyBase {
	protected $name = '';
	protected $formalname = '';
	protected $barename = '';
	protected $system = '';
	protected $system_version = '';

	protected function __construct ($component, $system, $version) {
		$component = $this->getComponentObject($component);
		$this->name = $component->name;
		$this->formalname = $component->option;
		$parts = explode('_', $this->formalname);
		$this->barename = isset($parts[1]) ? $parts[1] : $this->formalname;
		$this->system = $system;
		$this->system_version = $version;
		if(file_exists($this->absolute_path."/components/$this->formalname/language/".$this->getCfg('lang').'.php')) {
			require_once($this->absolute_path."/components/$this->formalname/language/".$this->getCfg('lang').'.php');
		}
		else if (file_exists($this->absolute_path."/components/$this->formalname/language/english.php")) {
			require_once($this->absolute_path."/components/$this->formalname/language/english.php");
		}
	}

	protected function __clone () {
		// Enforce singleton
	}

	protected function noMagicQuotes () {
		// Is magic quotes on?
		if (get_magic_quotes_gpc()) {
			// Yes? Strip the added slashes
			$_REQUEST = $this->remove_magic_quotes($_REQUEST);
			$_GET = $this->remove_magic_quotes($_GET);
			$_POST = $this->remove_magic_quotes($_POST);
			$_FILES = $this->remove_magic_quotes($_FILES, 'tmp_name');
		}
	}

	private function &remove_magic_quotes ($array, $exclude='') {
		foreach ($array as $k => &$v) {
			if (is_array($v)) $v = $this->remove_magic_quotes($v, $exclude);
			// Did apply stripslashes twice, why?  Removed to see what happens
			elseif ($k != $exclude) $v = stripslashes($v);
		}
		return $array;
	}

}

/**
* Component base logic for user side
*/

abstract class aliroComponentUserManager extends aliroComponentManager {
	private $func;
	private $method;
	private $classname;
	private $controller;
	private $page404 = false;
	public $menu = null;
	public $limit = 10;
	public $limitstart = 0;

	public function __construct ($component, $control_name, $alternatives, $default, $title, $system, $version, $menu) {
		parent::__construct($component, $system, $version);
		$this->menu = $menu;
		if ($title) $this->SetPageTitle($title);
		$this->func = $this->getParam ($_REQUEST, $control_name, $default);
		if (!preg_match('`[0-9a-zA-Z_\-]+`', $this->func)) new aliroPage404(T_('Invalid characters in control variable for request'));
		if (isset($alternatives[$this->func])) $this->method = $alternatives[$this->func];
		else $this->method = $this->func;
		$this->classname = $this->barename.'_'.$this->method.'_Controller';
		if (aliro::getInstance()->classExists($this->classname) AND method_exists($this->classname, 'getInstance')) $this->controller = call_user_func(array($this->classname, 'getInstance'), $this);
		else {
			new aliroPage404(sprintf(T_('Unable to locate class %s or has no getInstance method'), $this->classname));
			$this->page404 = true;
		}
	}

	public function activate() {
		if ($this->page404) return;
		$this->noMagicQuotes();
		$cmethod = $this->method;
		if (method_exists($this->controller,$cmethod)) $this->controller->$cmethod($this->func);
		else new aliroPage404(sprintf(T_('Unable to locate method %s in class %s'), $cmethod, $this->classname));
	}

}

abstract class aliroComponentControllers extends aliroFriendlyBase {
	protected $authoriser = null;
	protected $user = null;
	protected $menu = null;
	protected $params = null;
	protected $idparm = 0;
	public $pageNav = null;
	public $option = '';

	protected function __construct () {
		$this->authoriser = aliroAuthoriser::getInstance();
		$this->menu = $this->getMenu();
		if ($this->menu) $this->params = new aliroParameters($this->menu->params);
		else $this->params = new aliroParameters();
		$this->user = aliroUser::getInstance();
		$this->idparm = $this->getParam($_REQUEST, 'id', 0);
		$this->option = aliroRequest::getInstance()->getOption();
	}

	protected function __clone () {
		// Restricted to enforce singleton
	}

	public function makePageNav ($total) {
		$limit = $this->getUserStateFromRequest($this->option.'_page_limit', 'limit', intval($this->getCfg('list_limit')));
		$limitstart = $this->getUserStateFromRequest($this->option.'_page_limitstart', 'limitstart', 0 );
		$this->pageNav = new aliroPageNav($total, $limitstart, $limit );
	}

	protected function trackSelectedItems () {
		$selection = $this->getCurrentSelection();
		if (!empty($this->cidall)) {
			$selection = array_merge($selection, $this->cid);
			$selection = array_diff($selection, array_diff($this->cidall,$this->cid));
			$this->setCurrentSelection($selection);
		}
		return $selection;
	}
	
	protected function removeSelectedItems ($remove) {
		$selected = $this->getCurrentSelection();
		$this->setCurrentSelection(array_diff($selected, $remove));
	}
	
	protected function getCurrentSelection () {
		return isset($_SESSION[$this->session_list_name]) ? $_SESSION[$this->session_list_name] : array();
	}
	
	protected function setCurrentSelection ($selection) {
		if (empty($selection)) unset($_SESSION[$this->session_list_name]);
		else $_SESSION[$this->session_list_name] = $selection;
	}
	
}