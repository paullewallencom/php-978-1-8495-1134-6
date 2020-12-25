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
 * aliroMenuCreator is a singleton class that handles the logic of menu creation.
 * It complements the aliroMenuHandler (cached singleton) class which knows about
 * all the menu entries for the system.  It is used by menu modules in conjunction
 * with the aliroMenuHandler to obtain the raw material for building a menu.  The
 * aim is to provide all the logic for menus in the Aliro core, while leaving the
 * construction of XHTML (and maybe CSS) to add-on modules.
 *
 */

class aliroMenuCreator {
	protected static $instance = __CLASS__;
	protected $config = null;
	protected $handler = null;
	protected $currlink = '';

	protected function __construct () {
		$this->config = aliroCore::getInstance();
		$this->handler = aliroMenuHandler::getInstance();
		$itemid = aliroRequest::getInstance()->getItemid();
		if ($itemid) {
			$currmenu = $this->handler->getMenuByID($itemid);
			if ($currmenu) $this->currlink = $currmenu->link;
		}
	}

	protected function __clone () {
		// Null function - private to enforce singleton
	}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self::$instance());
	}

    protected function makeMenuLink($mitem, $params, $maxindent, $subactive) {
        $newlink = new aliroMenuLink ();
        $newlink->id = $mitem->id;
        $newlink->name = $mitem->name;
		$newlink->level = min($mitem->level,$maxindent);
        $newlink->link = $mitem->home ? aliroCore::getInstance()->getCfg('live_site') : aliroSEF::getInstance()->sefRelToAbs($mitem->link);
        // Active Menu highlighting
        $newlink->active = ($this->currlink == $mitem->link);
		$newlink->subactive = $subactive;
        // Set menu link class
        $newlink->opener = $mitem->browserNav;
        if ( $params->get('menu_images', 0)) {
            $menu_params = new aliroParameters($mitem->params);
            $menu_image = $menu_params->def( 'menu_image', -1 );
            if ($menu_image AND $menu_image <> '-1') {
            	$newlink->image = aliroCore::get('mosConfig_live_site').'/images/stories/'.$menu_image;
            	$newlink->image_last = $params->get('menu_images_align', 0);
            }
        }
        return $newlink;
    }

    /**
	* Get images for menu indentation
	*/
    public function getIndents( $params ) {
        $base = aliroCore::getInstance()->getCfg('live_site');
        $imgpath = $base.'/templates/'. aliroRequest::getInstance()->getTemplate() .'/images';

        for ( $i = 1; $i < 7; $i++ ) {
	        switch ($params->get( 'indent_image', 0 )) {

	            case '1':
	            // Default images
                $img[$i] = array("$base/images/M_images/indent$i.png", "Indent $i");
				break;

	            case '2':
	            // Use Params
                $img[$i] =  ('-1' == $params->get('indent_image'.$i, 0)) ? array (NULL, NULL) : array("$base/images/M_images/$parm", "Indent $i");
	            break;

	            case '3':
	            // None
            	$img[$i] = array(NULL,NULL);
            	break;

            	default:
            	// Template
                $img[$i] = array("$imgpath/indent$i.png", "Indent $i");
	            break;
    	    }
        }
        return $img;
    }

    /**
	* Construct a menu
	*/
    public function getMenuData ($params, $maxindent, $showAll=_ALIRO_SHOW_ACTIVE_SUBMENUS) {
		$menutype = $params->get('menutype', $this->handler->getDefaultType());
		$parentid = $params->get('parent_id', 0);
		$rows = $this->handler->getByParentOrder($menutype, true);
		$entries = $subactive = array();
		if (empty($rows)) return $entries;
		foreach ($rows as $i=>$row) $links[$row->id] = $i;
		
		$filter = array($parentid);
		foreach ($rows as $row) if (in_array($row->parent, $filter)) {
			array_push($filter, $row->id);
			$filtered[$links[$row->id]] = $row;
			if ($this->currlink == $row->link) $foundactive = true;
		}
		if (empty($filtered)) return array();
		unset ($rows);
		if (empty($foundactive) AND _ALIRO_SHOW_ACTIVE_SUBMENUS == $showAll) $showAll = _ALIRO_SHOW_NO_SUBMENUS;

		$show = array($parentid);
		foreach ($filtered as $row) {
			if (!in_array($row->parent, $show)) array_unshift($show, $row->parent);
			elseif (!$row->parent == $show[0]) array_shift($show);
			if ($this->currlink == $row->link) {
				array_push($show, $row->id);
				$parent = $row->parent;
				while ($parent != $parentid AND $parent != 0) {
					$subactive[$parent] = 1;
					$parent = $filtered[$links[$parent]]->parent;
				}
				break;
			}
		}
		foreach ($filtered as $row) if (_ALIRO_SHOW_ALL_SUBMENUS == $showAll OR (_ALIRO_SHOW_ACTIVE_SUBMENUS == $showAll AND in_array($row->parent, $show)) OR (_ALIRO_SHOW_NO_SUBMENUS == $showAll AND $parentid == $row->parent)) {
			$entries[] = $this->makeMenuLink($row, $params, $maxindent, isset($subactive[$row->id]));
		}
        return $entries;
    }

	public function createMenu ($params) {
   		// indent icons for levels 1 to 6 of indentation
   		$img = $this->getIndents($params);
		$showAll = $params->get('show_all', _ALIRO_SHOW_ACTIVE_SUBMENUS);
		
		// Get the menu entries - each one is an object with various properties:
		// name - the name of the menu entry
		// type - the type of menu entry, such as 'component_item_link' or 'url'
		// id - the id to be used in the XHTML (if set)
		// class - the class to be used in the XHTML
		// image - the URL for the image for this entry
        // image_last - non-zero if the image comes after the text
        // opener - the kind of opening requested e.g. new window, same window, ...
		$entries = $this->getMenuData($params, count($img), $showAll);
		if (0 == count($entries)) return "\n<!-- Menu with zero entries -->";

        $baselevel = $level = $entries[0]->level-1;
		$menuclass = $params->get('menu_class');
		$menuid = $params->get('menu_id');
		$divclass = $params->get('div_class');
		$divid = $params->get('div_id');
		$family = $params->get('family_active', 0);
		$text = "\n<div";
		if ($divclass) $text .= " class=\"$divclass\"";
		if ($divid) $text .= " id=\"$divid\"";
		$text .= '>';
        foreach ($entries as $entry) {
			if ($entry->level > $level) {
				$text .= "\n<ul";
				if ($menuclass) $text .= " class=\"$menuclass\"";
				if ($menuid) $text .= " id=\"$menuid\"";
				$text .= '>';
			}
			elseif ($entry->level == $level) $text .= '</li>';
        	// Terminate the previous top level entry if appropriate
			if ($entry->level < $level) {
				$text .= "\n</li>";
				while ($entry->level < $level) {
					$text .= '</ul></li>';
					$level--;
				}
			}
        	// If we're at top level, start a new entry
        	// if ($entry->level == 0) $text .= "\n<li>";
        	// If this is a submenu, then use an indent image
			$text .= ($level < 0) ? "\n<li class=\"first\">" : "\n<li>";
        	if ($entry->level > 0 AND !empty($img[$entry->level][0])) $iimage = '<img src="'. $img[$entry->level][0].'" alt="" />';
			else $iimage = '';
        	// Now for the actual menu link
			$text .= $this->makeLink ($entry, $iimage, $family);
			//if ($entry->level > 0) $text .= '</span>';
        	// Save the level we're currently at, ready for the next time round
        	$level = $entry->level;
        }
        // Terminate everything at the top level
		while ($baselevel < $level) {
			$text .= "\n</li></ul>";
			$level--;
		}
        $text .= "\n</div>";
        return $text;
	}
	
	protected function makeLink ($entry, $iimage, $family) {
		$text = '';
		if ($entry->image) {
			$image = '<img src="'. mamboCore::get('mosConfig_live_site') .'/images/stories/'. $entry->image .'" border="0" alt="'. $entry->name .'"/>';
			if (!$entry->image_last) $text .= $image;
		}
		if ($entry->active OR (1 == $family AND $entry->subactive)) $aclass = ' class="active"';
		elseif ($entry->subactive AND !$entry->active AND 2 == $family) $aclass = ' class="subactive"';
		else $aclass = '';
       	switch ($entry->opener) {
            // cases are slightly different
            case 1:
            // open in a new window
            $text .= '<a href="'. $entry->link .'" target="_blank"'. $aclass .'>'. $iimage.$entry->name .'</a>';
            break;
            
	        case 2:
           	// open in a Javascript popup window
           	$text .= "<a href=\"#\" onclick=\"javascript: window.open('". $entry->link ."', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\"". $aclass .">". $iimage.$entry->name ."</a>\n";
           	break;
           	
           	case 3:
           	// don't link it
           	$text .= '<span'. $aclass .'>'. $iimage.$entry->name .'</span>';
           	break;
           	
	        default:	
            // open in parent window
            $text .= '<a href="'. $entry->link .'"'. $aclass .'><span>'. $iimage.$entry->name .'</span></a>';
            break;
       	}
       	if ($entry->image_last) $text .= $image;
       	return $text;
	}
    
}
