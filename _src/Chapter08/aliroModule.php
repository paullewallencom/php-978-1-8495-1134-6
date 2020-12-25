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
 * aliroModuleHandler is a cached singleton class that looks after all the data
 * for modules within Aliro.  It is optimised towards creating useful data
 * structures in the constructor, which are then cached.  The access methods
 * are as simple as possible, so as to give the best run time performance.
 *
 * aliroModule is the object that corresponds to an entry in the module table.
 * In addition, it has methods to assist in the rendering of modules on the
 * browser screen.  Details of format are referred to the template, so that
 * control of XHTML is kept out of the core.
 *
 */

class aliroModuleHandler extends aliroCommonExtHandler {
	protected static $instance = __CLASS__;

	private $allModules = array();
	private $keyToSubscript = array();
	private $user_area_links = array();
	private $admin_area_links = array();
	private $allMenusByModule = array();
	private $allModulesByMenu = array();
	private $excludeModulesByMenu = array();
	private $distinct_user_side = array();
	private $visibleKeys = array();
	private $modulesByFormalName = array();

	protected $extensiondir = '/modules/';

	protected function __construct () {
		$query = "SELECT m1.*, (CASE WHEN m2.menuid = 0 THEN 'All' WHEN m2.menuid IS NULL THEN 'None' ELSE 'Varies' END) pages"
		." FROM `#__modules` m1 LEFT JOIN `#__modules_menu` m2 ON m1.id = m2.moduleid"
		." GROUP BY m1.id ORDER BY m1.position, m1.ordering";
		$database = aliroCoreDatabase::getInstance();
		$this->allModules = $database->doSQLget($query, 'aliroModule');
		$translatePages = array ('All' => T_('All'), 'None' => T_('None'), 'Varies' => T_('Varies'));
		foreach ($this->allModules as $sub=>&$module) {
			$this->keyToSubscript[$module->id] = $sub;
			$this->modulesByFormalName[$module->module] = $sub;
			$module->pages = $translatePages[$module->pages];
			if ($module->published) {
				$exin = $module->exclude ? 'exclude' : 'include';
				if ($module->admin & 1) $this->user_area_links[$module->position][$exin][] = $module->id;
				if ($module->admin & 2) $this->admin_area_links[$module->position][$exin][] = $module->id;
			}
			if ($module->admin & 1) $distinct_user_side[$module->module] = 1;
		}
		if (isset($distinct_user_side)) {
			$this->distinct_user_side = array_keys($distinct_user_side);
			sort($this->distinct_user_side);
		}
		$menus = $database->doSQLget ("SELECT * FROM #__modules_menu");
		foreach ($menus as $menu) {
			$this->allMenusByModule[$menu->moduleid][] = (int) $menu->menuid;
		}
		$modmenus = $database->doSQLget("SELECT m1.menuid, m2.moduleid FROM `#__modules_menu` m1"
		." INNER JOIN `#__modules_menu` m2 ON m1.menuid = m2.menuid OR m2.menuid =0"
		." INNER JOIN `#__modules` m ON m.id = m2.moduleid WHERE m.exclude = 0"
		." GROUP BY m1.menuid, m2.moduleid");
		foreach ($modmenus as $menu) $this->allModulesByMenu[$menu->menuid][] = $menu->moduleid;
		$modmenus = $database->doSQLget("SELECT m1.menuid, m1.moduleid FROM `#__modules_menu` m1 WHERE m1.menuid NOT IN "
		." (SELECT m2.menuid FROM `#__modules_menu` m2"
		." INNER JOIN `#__modules` m ON m.id = m2.moduleid WHERE m1.moduleid = m2.moduleid AND m.exclude != 1)");
		foreach ($modmenus as $menu) $this->excludeModulesByMenu[$menu->menuid][] = $menu->moduleid;
	}

	// Singleton accessor with cache
	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = parent::getCachedSingleton(self::$instance));
	}

	public function makeModuleFromExtension ($extension) {
		$newmodule = new aliroModule();
		$newmodule->title = T_('Please select a title');
		// Can't set ordering until we know position
		$newmodule->published = 1;
		$newmodule->module = $extension->formalname;
		$newmodule->showtitle = 1;
		$newmodule->admin = $extension->admin;
		$newmodule->class = $extension->class;
		$newmodule->adminclass = $extension->adminclass;
		return $newmodule;
	}

	private function getVisibleKeys ($position, $isAdmin) {
		$authoriser = aliroAuthoriser::getInstance();
		if (isset($this->visibleKeys[$position][$isAdmin])) return $this->visibleKeys[$position][$isAdmin];

		$result = array();
		if ($isAdmin) {
			$inelements = isset($this->admin_area_links[$position]['include']) ? $this->admin_area_links[$position]['include'] : array();
			$exelements = isset($this->admin_area_links[$position]['exclude']) ? $this->admin_area_links[$position]['exclude'] : array();
		}
		else {
			$inelements = isset($this->user_area_links[$position]['include']) ? $this->user_area_links[$position]['include'] : array();
			$exelements = isset($this->user_area_links[$position]['exclude']) ? $this->user_area_links[$position]['exclude'] : array();
		}

		$currentmenu = aliroRequest::getInstance()->getItemid();
		if (isset($this->excludeModulesByMenu[$currentmenu])) {
			$exelements = array_diff($exelements, $this->excludeModulesByMenu[$currentmenu]);
		}
		foreach ($exelements as $element) if ($authoriser->checkUserPermission ('view', 'aliroModule', $element)) $result[] = $element;
		if (!isset($this->allModulesByMenu[$currentmenu])) $currentmenu = 0;
		if (isset($this->allModulesByMenu[$currentmenu])) {
			$inelements = array_intersect($inelements, $this->allModulesByMenu[$currentmenu]);
			foreach ($inelements as $element) if ($authoriser->checkUserPermission ('view', 'aliroModule', $element)) $result[] = $element;
		}

		$this->visibleKeys[$position][$isAdmin] = $result;
		return $result;
	}

	public function countModules ($position, $isAdmin) {
		return count($this->getVisibleKeys ($position, $isAdmin));
	}

	public function getModules ($position, $isAdmin) {
		$keys = $this->getVisibleKeys ($position, $isAdmin);
		$usercountry = aliroUser::getInstance()->countrycode;
		foreach ($keys as $key) {
			$module = $this->allModules[$this->keyToSubscript[$key]];
			if ($usercountry AND $module->incountry) {
				if (false !== strpos($module->incountry, $usercountry)) $result[] = $module;
			}
			elseif ($usercountry AND $module->excountry) {
				if (false === strpos($module->excountry, $usercountry)) $result[] = $module;
			}
			else $result[] = $module;
		}
		return isset($result) ? $result : array();
	}

	public function getModuleByID ($id) {
		return isset($this->allModules[$this->keyToSubscript[$id]]) ? $this->allModules[$this->keyToSubscript[$id]] : null;
	}
	
	public function getModuleByFormalName ($formalname) {
		return isset($this->modulesByFormalName[$formalname]) ? $this->allModules[$this->modulesByFormalName[$formalname]] : null;
	}

	public function getSelectedModules ($position='', $formalname='', $search='', $admin=false) {
		$results = array();
		foreach ($this->allModules as $module) {
			if ($admin) {
				if (!($module->admin & 2)) continue;
			}
			elseif (!($module->admin & 1)) continue;
			if ($position AND $module->position != $position) continue;
			if ($formalname AND $module->module != $formalname) continue;
			if ($search AND strpos(strtolower($module->title), $search) === false) continue;
			$results[] = $module;
		}
		return $results;
	}

	public function getModulesByPosition ($admin) {
		$results = array();
		$check = $admin ? 2 : 1;
		foreach ($this->allModules as $module) {
			if ($module->admin & $check) $results[$module->position][] = $module;
		}
		return $results;
	}

	public function getMenus ($module_id) {
		return isset($this->allMenusByModule[$module_id]) ? $this->allMenusByModule[$module_id] : array();
	}

	public function getDistinctNames () {
		return $this->distinct_user_side;
	}

	public function deleteModules ($ids) {
		foreach ($ids as &$id) $id = intval($id);
		$idlist = implode (',', $ids);
		$database = aliroCoreDatabase::getInstance();
		$database->doSQL ("DELETE FROM #__modules WHERE id IN ($idlist)");
		$database->doSQL ("DELETE FROM #__modules_menu WHERE moduleid IN ($idlist)");
		$this->clearCache();
	}

	public function publishModules ($ids, $new_publish) {
		foreach ($ids as &$id) $id = intval($id);
		$new_publish = intval($new_publish);
		$idlist = implode (',', $ids);
		$database = aliroCoreDatabase::getInstance();
		$database->doSQL ("UPDATE #__modules SET published = $new_publish WHERE id IN ($idlist)");
		$this->clearCache();
	}

	public function changeOrder ($id, $direction) {
		$module = $this->allModules[$this->keyToSubscript[$id]];
		$movement = 'down' == $direction ? 15 : -15;
		$this->updateOrdering (array($id => $module->ordering + $movement));
	}

	public function updateOrdering ($orders) {
		foreach ($orders as $id=>$order) {
			$module =  $this->allModules[$this->keyToSubscript[$id]];
			if ($module->ordering != $order) $changes[$id] = $order;
		}
		foreach ($this->allModules as $module) {
			$ordering = isset($changes[$module->id]) ? $changes[$module->id] : $module->ordering;
			$allmodules[$module->position][$ordering] = $module->id;
		}
		$changed = false;
		$query = "UPDATE #__modules SET ordering = CASE ";
		foreach ($allmodules as $position=>$orderings) {
			$order = 10;
			ksort($orderings);
			foreach ($orderings as $ordering=>$id) {
				$module = $this->allModules[$this->keyToSubscript[$id]];
				if ($order != $module->ordering) {
					$query .= "WHEN id = $id THEN $order ";
					$changed = true;
				}
				$order += 10;
			}
		}
		if ($changed) {
			$query .= 'ELSE ordering END';
			aliroCoreDatabase::getInstance()->doSQL ($query);
			$this->clearCache();
		}
	}

}

/**
* Module database table class
* Aliro
*/
class aliroModule extends aliroDatabaseRow {
	protected $DBclass = 'aliroCoreDatabase';
	protected $tableName = '#__modules';
	protected $rowKey = 'id';
	protected $handler = 'aliroModuleHandler';
	protected $formalfield = 'module';

	// overloaded check function
	public function check() {
		// check for presence of a name
		if (trim( $this->title ) == '') {
			$this->_error = T_('Your Module must contain a title.');
			return false;
		}
		return true;
	}

	public function getParams () {
	    $params = new aliroParameters ($this->params);
	    return $params;
	}

	public function loadLanguage () {
		// check for custom language file
		$basepath = _ALIRO_ABSOLUTE_PATH.'/modules/'.$this->module;
		$path = $basepath.aliroCore::get('mosConfig_lang').'.php';
		if (file_exists( $path )) include( $path );
		else {
			$path = $basepath.'.en.php';
			if (file_exists( $path )) include( $path );
		}
	}

	public function renderModule ($area, $template, $i, $count) {
		$this->loadLanguage();
		$params = $this->getParams();
		$title = $this->showtitle ? $this->title : '';
		$moduleclass = ($this->admin & 2) ? $this->adminclass : $this->class;
		$split = explode(',', $moduleclass);
		$modobject = aliroRequest::getInstance()->getClassObject($split[0]);
		$activate = isset($split[1]) ? $split[1] : 'activate';
		try {
			$modobject->$activate($this, $content, $area, $params);
    	} catch (databaseException $exception) {
    		$message = sprintf(T_('A database error occurred on %s at %s while processing %s'), date('Y-M-d'), date('H:i:s'), $split[0]);
    		$errorkey = "SQL/{$exception->getCode()}/{$split[0]}/$exception->dbname/{$exception->getMessage()}/$exception->sql";
    		aliroErrorRecorder::getInstance()->recordError($message, $errorkey, $message, $exception);
    		aliroRequest::getInstance()->setErrorMessage($message, _ALIRO_ERROR_SEVERE);
    		return '';
    	}
		$method = 'moduleStyle'.$area->style;
		return $template->$method($this->suffix, $title, $content, $area->name, $i, $count);
	}

	public function renderModuleTitle ($area, $template, $i, $count) {
		$title = $this->showtitle ? $this->title : '';
		$method = 'moduleStyle'.$area->style;
		return $template->$method($this->suffix, $title, '', $area->name, $i, $count);
	}

}
