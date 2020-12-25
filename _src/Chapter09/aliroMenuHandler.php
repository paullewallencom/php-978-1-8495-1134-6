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
 * Basic menu classes are here.  aliroAdminMenu is the simple class that provides
 * objects corresponding to rows in the admin menu table.
 *
 * aliroMenu likewise corresponds to rows in the user menu table.  It has a few
 * extra methods.
 *
 * aliroMenuLink is the simple data class that is used to communicate between the
 * aliroMenuCreator and menu modules.  This allows all the menu logic to remain in
 * the Aliro core, while all the presentation is handled by installable modules.
 *
 * aliroMenuHandler does most of the work for manipulating menu data.  It is a
 * cached singleton, so it only loads its data from the database periodically in
 * normal operation.
 *
 */

final class aliroMenuType extends aliroDatabaseRow {
	protected $DBclass = 'aliroCoreDatabase';
	protected $tableName = '#__menutype';
	protected $rowKey = 'id';
	
	public function store ($updateNulls=false) {
		if (0 == $this->id) {
			$database = aliroCoreDatabase::getInstance();
			$database->setQuery("SELECT id FROM #__admin_menu WHERE link = 'index.php?core=cor_menus&act=type'");
			$menutop = $database->loadResult();
			$database->doSQL("INSERT INTO #__admin_menu (name, link, type, published, parent, checked_out_time) VALUES('$this->name', 'index.php?core=cor_menus&task=list&menutype=$this->type', 'core', 1, $menutop, '{$database->dateNow()}')");
		}
		$result = parent::store($updateNulls);
		aliroMenuHandler::getInstance()->clearCache(true);
		return $result;
	}
}

// This is subclassed with two different names for backwards compatibility only
abstract class aliroAbstractMenuItem extends aliroDatabaseRow {
	protected $DBclass = 'aliroCoreDatabase';
	protected $tableName = '#__menu';
	protected $rowKey = 'id';
	public $subjectName = 'aliroMenuItem';

	public function load( $oid=null ) {
		trigger_error ('Should not ->load() a menu - aliroMenuHandler can provide the whole menu using getMenuByID($id)');
		echo aliroRequest::trace();
    }

    public function getParams () {
		return new aliroParameters($this->params, $this->parmspec);
    }

    public function linkComponentData ($component) {
    	$this->name = $component->name;
    	$this->link = 'index.php?option='.$component->option;
    	$this->type = 'component';
    	$this->componentid = $component->id;
    	$this->component = $component->option;
    	$extension = aliroExtensionHandler::getInstance()->getExtensionByName($component->option);
    	$this->xmlfile = $extension->xmlfile;
    }

}

// The class that is used for menu item objects in Aliro
final class aliroMenuItem extends aliroAbstractMenuItem {}

// Provided only for compatibility
final class mosMenu extends aliroAbstractMenuItem {}

final class aliroMenuLink {
	public $id = 0;
	public $name = '';
	public $link = '';
	public $image = '';
	public $opener = '';
	public $image_last = 0;
	public $level = 0;
	public $active = false;
	public $subactive = false;
}

/**
* Menu handler
*/
final class aliroMenuHandler extends cachedSingleton {
	private $menutypes = array();
    private $menus = array();
    private $counts = array();
    private $byParentOrder = array();
    private $main_home = null;
    private $default_type = '';
    private $type_max_ordering = 0;

    protected static $instance = __CLASS__;

    /**
	* Constructor - protected to enforce singleton
	*/
    protected function __construct () {
    	$database = aliroCoreDatabase::getInstance();
    	$menutypes = $database->doSQLget("SELECT * FROM #__menutype ORDER BY ordering", 'aliroMenuType');
		if (count($menutypes)) {
			$this->default_type = $menutypes[0]->type;
			$finaltype = end($menutypes);
			$this->type_max_ordering = $finaltype->ordering;
			foreach ($menutypes as $type) $this->menutypes[$type->type] = $type;
		}
        $sql = "SELECT m.* FROM #__menu AS m INNER JOIN #__menutype AS t ON m.menutype = t.type ORDER BY t.ordering, m.ordering";
        $menus = $database->doSQLget($sql, 'aliroMenuItem');
       	$homes = 0;
        foreach ($menus as $key=>$menu) {
        	if ($menu->home) {
        		$homes++;
        		if (is_null($this->main_home) AND $menu->published AND 0 == $menu->parent) {
    	    		$this->main_home = $menu;
        		}
	        	else $menu->home = 0;
        	}
             // Ensure that published is always 0 or 1
        	if ($menu->published) {
        		$menu->published = 1;
        		if (!isset($first) AND 0 == $menu->parent) $first = $menu;
        	}
        	else $menu->published = 0;
        	$this->menus[$menu->id] = $menu;
        	
            $this->byParentOrder[$menu->menutype][$menu->parent][] = $menu->id;
            if (isset($this->counts[$menu->menutype][$menu->published])) $this->counts[$menu->menutype][$menu->published]++;
            else $this->counts[$menu->menutype][$menu->published] = 1;
        }
        if ($homes > (isset($this->main_home) ? 1 : 0)) $needfix = true;
        if (!isset($this->main_home) AND isset($first)) {
        	$this->main_home = $first;
        	$first->home = 1;
        	$needfix = true;
        }
        if (!empty($needfix)) {
        	$idforhome = isset($this->main_home) ? $this->main_home->id : 0;
        	$database->doSQL("UPDATE #__menu SET home = IF(id=$idforhome, 1, 0)");
        }
        unset($menutypes,$menus);
    }

    /**
	* Singleton accessor with cache
	*/
	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = parent::getCachedSingleton(self::$instance));
	}
	
	public function getNextMenutypeOrdering () {
		return $this->type_max_ordering + 10;
	}

    public function getMenutypes () {
    	return array_keys($this->menutypes);
    }
    
    public function getDefaultType () {
    	return $this->default_type;
    }
    
    public function deleteMenutypes ($ids) {
    	$list = implode(',', array_map('intval', $ids));
    	if ($list) {
    		$database = aliroCoreDatabase::getInstance();
    		$database->doSQL("DELETE FROM #__menutype WHERE id IN ($list)");
    		$database->doSQL("DELETE LOW_PRIORITY m FROM `#__menu` AS m LEFT JOIN #__menutype AS t ON m.menutype = t.type WHERE t.type IS NULL");
    		$database->doSQL("DELETE LOW_PRIORITY m FROM `#__admin_menu` AS m LEFT JOIN #__menutype AS t ON m.link = CONCAT('index.php?core=cor_menus&task=list&menutype=',t.type)"
    		." WHERE m.link LIKE 'index.php?core=cor_menus&task=list&menutype=%' AND t.type IS NULL");
			$this->clearCache(true);
    	}
    }

    public function getMenutypeObjects () {
    	$types = array_values($this->menutypes);
    	new aliroObjectSorter($types, 'ordering');
    	return $types;
    }
    
    public function changeMenuType ($old, $type, $name) {
   		$database = aliroCoreDatabase::getInstance();
    	if ($old AND $old != $type) {
    		$database->doSQL("UPDATE #__menu SET menutype = '$type' WHERE menutype = '$old'");
    		$database->doSQL("UPDATE #__admin_menu SET link = 'index.php?core=cor_menus&task=list&menutype=$type', name = '$name' WHERE link = CONCAT('index.php?core=cor_menus&task=list&menutype=','$old')");
			$this->clearCache(true);
    	}
    	else $database->doSQL("UPDATE #__admin_menu SET name = '$name' WHERE link = CONCAT('index.php?core=cor_menus&task=list&menutype=','$type')");
		aliroAdminMenuHandler::getInstance()->clearCache(true);
    }

    public function getMenuByID ($id) {
    	return isset($this->menus[$id]) ? $this->menus[$id] : null;
    }

    public function makeMenuHomePage ($id) {
    	if (isset($this->menus[$id])) {
    		aliroCoreDatabase::getInstance()->doSQL("UPDATE #__menu SET home = IF(id=$id,1,0)");
    		$this->clearCache(true);
    	}
    }
    
    public function getMenuCount ($type='', $published=1) {
    	if (!$type) $type = $this->default_type;
        return isset($this->counts[$type][$published]) ? $this->counts[$type][$published] : 0;
    }

    public function getCountByTypeComponentID ($type, $sections) {
		$count = 0;
		foreach ($this->menus as $menu) {
			if ($menu->menutype == $type AND in_array($menu->componentid, $sections) AND 1 == $menu->published) $count++;
		}
		return $count;
    }

    public function getIDByMenutypeQuery ($type, $query) {
    	$query = str_replace('&amp;', '&', $query);
        foreach ($this->menus as $menu) {
            if ($menu->published == 1 AND ($type == '*' OR $menu->menutype == $type) AND $menu->link == 'index.php?'.$query) return $menu->id;
        }
        return null;
    }

    public function getAllMenusByMenutype ($type) {
    	$menus = array();
    	foreach ($this->menus as $menu) if ($menu->menutype == $type) $menus[] = $menu;
    	return $menus;
    }

    public function getAllMenusByType ($type) {
    	$menus = array();
    	foreach ($this->menus as $menu) if ($menu->type == $type) $menus[] = $menu;
    	return $menus;
    }

    public function getMenusByIDTypes ($componentid, $types) {
    	$menus = array();
    	foreach ($this->menus as $menu) {
    		if ($componentid != $menu->componentid) continue;
    		foreach ($types as $type) if (false !== strpos($menu->link, $type)) {
    			$menus = $menu;
    			continue;
    		}
    	}
    	return $menus;
    }

    private function getIDLikeQuery ($query_items, $published=false) {
    	$min = $ordering = 999999;
    	$result = 0;
        foreach ($this->menus as $menu) {
        	if (substr($menu->link,0,10) != 'index.php?' OR ($published AND !$menu->published)) continue;
        	$menutype = $this->menutypes[$menu->menutype];
        	$link = str_replace('&amp;', '&', substr($menu->link,10));
        	$link_items = explode('&', $link);
        	$diff = count(array_diff($link_items, $query_items)) + count(array_diff($query_items, $link_items));
        	if ($diff < $min) {
        		$min = $diff;
        		$ordering = $menutype->ordering;
        		$result = $menu->id;
        	}
        	elseif ($diff == $min AND $menutype->ordering < $ordering) {
        		$result = $menu->id;
        		$ordering = $menutype->ordering;
        	}
        }
        if ($min AND isset($_SESSION['aliro_Itemid']) AND isset($this->menus[$_SESSION['aliro_Itemid']])) $result = $_SESSION['aliro_Itemid'];
        return $result;
    }

    public function matchURL ($published=true) {
    	if (!isset($_REQUEST['option'])) {
    		$this->setHome();
    		$result = $this->getHome();
    	}
    	else {
	    	if ($_SERVER['QUERY_STRING']) $query_items = explode('&', $_SERVER['QUERY_STRING']);
		   	else $query_items = array();
   			foreach ($_POST as $name=>$value) $query_items[] = $name.'='.$value;
	    	$link = $this->getIDLikeQuery($query_items, $published);
    		if ($link) $result = $this->menus[$link];
    		else $result = null;
    	}
        if ($result) {
            $optionstring = 'option='.aliroRequest::getInstance()->getOption();
            if (false === strpos($result->link, $optionstring)) return null;
            $_SESSION['aliro_Itemid'] = $result->id;
        }
        return $result;
   }

    public function getIDByTypeCid ($type, $componentid, $unpublished=false) {
        foreach ($this->menus as $menu) {
            if (($unpublished OR $menu->published == 1) AND ('*' == $type OR $menu->type == $type) AND $menu->componentid == $componentid) return $menu->id;
        }
        return null;
    }

    public function getGlobalBlogSectionCount () {
        $count = 0;
        foreach ($this->menus as $menu) {
            if ($menu->type == 'content_blog_section' AND $menu->published == 1 AND $menu->componentid == 0) $count++;
        }
        return $count;
    }

    private function addMenus ($menutype, &$result, $menukeys, $published, $level) {
        $authoriser = aliroAuthoriser::getInstance();
    	foreach ($menukeys as $key) {
    		$menu = $this->menus[$key];
    		$menu->level = $level;
    		if ($published AND !$menu->published) continue;
            if (!_ALIRO_IS_ADMIN AND !$authoriser->checkUserPermission ('view', $menu->subjectName, $menu->id)) continue;
            $result[] = $menu;
    		if (isset($this->byParentOrder[$menutype][$menu->id])) $this->addMenus($menutype, $result, $this->byParentOrder[$menutype][$menu->id], $published, $level+1);
    	}
    }

    public function &getByParentOrder ($menutype, $published=1) {
        $result = array();
        if (isset($this->byParentOrder[$menutype][0])) {
        	$this->addMenus($menutype, $result, $this->byParentOrder[$menutype][0], $published, 0);
        }
        return $result;
    }
	
	public function getContentMenuInfo () {
		$entries = $this->getByParentOrder($this->default_type);
		foreach ($entries as $menu) {
			$parsed = parse_url($menu->link);
			if (isset($parsed['query'])) {
				parse_str($parsed['query'], $parms);
				if (isset($parms['option']) AND 'com_content' == $parms['option']) {
					if (isset($parms['task'])) $result[$parms['task']][$menu->id] = $parms;
				}
			}
		}
		return isset($result) ? $result : array();
	}

    public function getHome () {
    	return isset($this->main_home) ? $this->main_home : null;
    }

    public function setHome () {
    	if ($this->main_home) {
	        $requests = explode ('&', substr($this->main_home->link, 10));
    	    foreach ($requests as $request) {
        	    $parts = explode ('=', $request);
            	if (count($parts == 2)) $_REQUEST[$parts[0]] = $_POST[$parts[0]] = $parts[1];
        	}
    	}
        return isset($_REQUEST['option']) ? $_REQUEST['option'] : null;
    }

    public function updateNames ($oldname, $newname, $type) {
    	$database = aliroCoreDatabase::getInstance();
    	$database->doSQL("UPDATE #__menu SET name='$newname' WHERE name='$oldname' AND type='$type'");
    	$this->clearCache(true);
    }

    public function publishMenus ($ids, $new_publish, $type=null) {
		foreach ($ids as &$id) $id = intval($id);
		$new_publish = intval($new_publish);
		$idlist = implode (',', $ids);
		$database = aliroCoreDatabase::getInstance();
		$sql = "UPDATE #__menu SET published = $new_publish WHERE id IN ($idlist)";
		if ($type) $sql .= " AND type='$type'";
		$database->doSQL ($sql);
		$this->clearCache(true);
    }

    public function changeOrder ($id, $direction, $menutype) {
		$menu = $this->getMenuByID($id);
		$movement = 'down' == $direction ? 15 : -15;
		$this->updateOrdering (array($id => $menu->ordering + $movement), $menutype);
    }

	public function updateOrdering ($orders, $menutype) {
		foreach ($orders as $id=>$order) {
			$menu =  $this->getMenuByID($id);
			if ($menu->ordering != $order) $changes[$id] = $order;
		}
		foreach ($this->getByParentOrder($menutype, 0) as $menu) {
			$ordering = isset($changes[$menu->id]) ? $changes[$menu->id] : $menu->ordering;
			$allmenus[$menu->level][$ordering] = $menu->id;
		}
		$changed = false;
		$query = "UPDATE #__menu SET ordering = CASE ";
		foreach ($allmenus as $level=>$orderings) {
			$order = 10;
			ksort($orderings);
			foreach ($orderings as $ordering=>$id) {
				$menu = $this->getMenuByID($id);
				if ($order != $menu->ordering) {
					$query .= "WHEN id = $id THEN $order ";
					$changed = true;
				}
				$order += 10;
			}
		}
		if ($changed) {
			$query .= 'ELSE ordering END';
			aliroCoreDatabase::getInstance()->doSQL ($query);
			$this->clearCache(true);
		}
	}

	public function setPathway ($Itemid) {
        if ($Itemid) {
            $menu = $this->getMenuByID($Itemid);
            if ($menu->parent) $this->setPathway($menu->parent);
            $pathway = aliroPathway::getInstance();
            $pathway->addItem($menu->name, $menu->link."&Itemid=$Itemid");
        }
    }

    public function deleteMenus ($cid) {
    	if (is_array($cid)) {
    		foreach ($cid as &$id) $id = intval($id);
    		$idlist = implode(',', $cid);
    	}
    	else $idlist = intval($cid);
    	$database = aliroCoreDatabase::getInstance();
    	$database->doSQL ("DELETE FROM #__menu WHERE id IN($idlist)");
    	$this->clearCache(true);
    	self::$instance = __CLASS__;
    }

    public function saveMenu ($menu) {
    	if ($menu instanceof aliroMenuItem) {
    		$database = aliroCoreDatabase::getInstance();
    		if ($menu->id == 0) {
	    		$menu->parent = intval($menu->parent);
	    		$database->setQuery ("SELECT MAX(ordering) FROM `#__menu` WHERE `parent` = $menu->parent GROUP BY `parent`");
	    		$menu->ordering = $database->loadResult() + 1;
    		}
    		if (!$menu->store()) trigger_error ('Store menu object failed');
    		$this->clearCache(true);
    	}
    	else trigger_error ('Asked to store something not a menu object');
    }

}