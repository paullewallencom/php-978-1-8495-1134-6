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
 * aliroCache emulates the PEAR light cache, but is much less code
 *
 */

class aliroSimpleCache extends aliroBasicCache {
	protected $group = '';
	protected $idencoded = '';
	protected $caching = true;
	protected $timeout = _ALIRO_HTML_CACHE_TIME_LIMIT;
	protected $sizelimit = _ALIRO_HTML_CACHE_SIZE_LIMIT;
	protected $livesite = '';
	
	public function __construct ($group, $maxsize=0) {
		if ($group) $this->group = $group;
        else trigger_error ('Cannot create cache without specifying group name');
		if ($maxsize) $this->sizelimit = $maxsize;
	}

	protected function getGroupPath () {
		return $this->getBasePath()."html/$this->group/";
	}

	protected function makeDirectory ($dirpath) {
		return new aliroDirectory($dirpath);
	}

	protected function getCachePath ($name) {
		return $this->getGroupPath().$name;
	}
	
	public function setTimeout ($timeout) {
		$this->timeout = $timeout;
	}

	public function clean () {
		$path = $this->getGroupPath();
		$dir = $this->makeDirectory($path);
		$dir->deleteFiles();
	}

	public function get ($id) {
		$this->idencoded = $this->encodeID($id);
		return $this->retrieve ($this->idencoded);
	}

	public function save ($data, $id=null, $reportSizeError=true) {
		if ($id) $this->idencoded = $this->encodeID($id);
		return $this->store ($data, $this->idencoded, $reportSizeError);
	}
	
	protected function encodeID ($id) {
		return md5(serialize($id).$this->livesite);
	}
}

final class aliroCache extends aliroSimpleCache {

	public function __construct ($group, $maxsize=0) {
		$this->caching = aliroCore::getInstance()->getCfg('caching') ? true : false;
		$this->livesite = aliroCore::getInstance()->getCfg('live_site');
		parent::__construct($group, $maxsize);
	}

	public function call () {
		$arguments = func_get_args();
		$cached = $this->caching ? $this->get($arguments) : null;
		if (!$cached) {
			ob_start();
			ob_implicit_flush(false);
			$function = array_shift($arguments);
			call_user_func_array($function, $arguments);
			$html = ob_get_contents();
			ob_end_clean();
			$cached = new stdClass();
			$cached->html = $html;
			if ($this->caching) {
			    aliroRequest::getInstance()->setMetadataInCache($cached);
				$cached->pathway = aliroPathway::getInstance()->getPathway();
				$this->save($cached);
			}
		}
		else {
		    aliroRequest::getInstance()->setMetadataFromCache($cached);
		    aliroPathway::getInstance()->setPathway($cached->pathway);
		}
		echo $cached->html;
	}

	public static function deleteAll () {
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/singleton/');
		$dir->deleteContents(true);
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/html/');
		$dir->deleteContents(true);
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/rssfeeds/');
		$dir->deleteContents(true);
		$fmanager = aliroFileManager::getInstance();
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/singleton/index.html', 0644);
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/html/index.html', 0644);
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/rssfeeds/index.html', 0644);
	}
}

/**
* Class to support function caching
* with backwards compatibility for Mambo and Joomla
*/
class mosCache {
    /**
	* @return object A function cache object
	*/
    static function getCache ($group) {
        return new aliroCache ($group);
    }
    /**
	* Cleans the cache
	*/
    public static function cleanCache ($group=false) {
        if (aliroCore::get('mosConfig_caching')) {
            $cache = mosCache::getCache( $group );
            $cache->clean ($group);
        }
    }
}
