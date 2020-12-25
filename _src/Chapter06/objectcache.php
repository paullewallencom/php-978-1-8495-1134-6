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
 * These classes provide cache logic, and for no special reason, the profiling
 * logic that supports the timing of operations, aliroProfiler.
 *
 * cachedSingleton is the base for building a singleton class whose internal
 * data is cached.  It is used extensively within the core, especially for the
 * handlers that look after information about the main building blocks of the
 * CMS, such as menus, components, modules, etc.  It provides common code for
 * them so that it becomes simple to create a cached singleton object.
 *
 * aliroBasicCache is the class containing rudimentary cache operations.  It
 * was initially independently developed, and subsequently modified to contain
 * the features of CacheLite.  Except that a number of decisions have been
 * taken as well as the code being exclusively PHP5 - these factors make the
 * code a lot simpler.
 *
 * aliroSingletonObjectCache does any special operations needed in the handling
 * of cached singletons, extending aliroBasicCache.
 *
 * Further cache related code that emulates the services of CacheLite (more or
 * less) is found elsewhere (in aliroCentral.php at the time of writing).
 *
 */

class aliroProfiler {
    private $start=0;
    private $prefix='';

    function __construct ( $prefix='' ) {
        $this->start = $this->getmicrotime();
        $this->prefix = $prefix;
    }

	public function reset () {
		$this->start = $this->getmicrotime();
	}

    public function mark( $label ) {
        return sprintf ( "\n$this->prefix %.3f $label", $this->getmicrotime() - $this->start );
    }

    public function getElapsed () {
    	return $this->getmicrotime() - $this->start;
    }

    private function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
}

abstract class cachedSingleton {

	protected function __clone () { /* Enforce singleton */ }

	protected static function getCachedSingleton ($class) {
		$objectcache = aliroSingletonObjectCache::getInstance();
		$object = $objectcache->retrieve($class);
		if ($object == null OR !($object instanceof $class)) {
			$object = new $class();
			$objectcache->store($object);
		}
		return $object;
	}

	public function clearCache ($immediate=false) {
		$objectcache = aliroSingletonObjectCache::getInstance();
		$classname = get_class($this);
		$objectcache->delete($classname);
		if ($immediate) {
			$instancevar = $classname.'::$instance';
			eval("$instancevar = '$classname';");
		}
	}
	
	public function cacheNow () {
		aliroSingletonObjectCache::getInstance()->store($this);
	}

}

abstract class aliroBasicCache {
	private static $mops = array();
	protected $sizelimit = 0;
	protected $timeout = 0;

	public function __destruct () {
		foreach (self::$mops as $mop) if ($mop) shmop_close($mop);
	}

	protected function getBasePath () {
		return _ALIRO_SITE_BASE.'/cache/';
	}

	abstract protected function getCachePath ($name);

	public function store ($object, $cachename='', $reportSizeError=true) {
		$path = $this->getCachePath($cachename ? $cachename : get_class($object));
		clearstatcache();
		$dir = dirname($path);
		if (!file_exists($dir)) $this->getFileManager()->createDirectory ($dir);
		if (is_object($object)) $object->aliroCacheTimer = time();
		else {
			$givendata = $object;
			$object = new stdClass();
			$object->aliroCacheData = $givendata;
			$object->aliroCacheTimer = -time();
		}
		$s = serialize($object);
		$s .= md5($s);
		if (strlen($s) > $this->sizelimit) {
			if ($reportSizeError) trigger_error(sprintf($this->T_('Cache failed on size limit, cached class %s, actual size %s, limit %s'),get_class($object), strlen($s), $this->sizelimit));
			return false;
		}
		$result = is_writeable(dirname($path)) ? @file_put_contents($path, $s, LOCK_EX) : false;
		if (!$result) {
			trigger_error(sprintf($this->T_('Cache failed on disk write, class %s, path %s'), get_class($object), $path));
			@unlink($path);
		}
		return $result;
	}
	
	protected function getFileManager () {
		return aliroFileManager::getInstance();
	}

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}
	
	public function retrieve ($class, $time_limit = 0) {
		// $timer = class_exists('aliroProfiler') ? new aliroProfiler() : null;
		$result = null;
		$path = $this->getCachePath($class);
		if (file_exists($path) AND ($string = @file_get_contents($path))) {
			$s = substr($string, 0, -32);
			$object = ($s AND (md5($s) == substr($string, -32))) ? unserialize($s) : null;
			if (is_object($object)) {
				$time_limit = $time_limit ? $time_limit : $this->timeout;
				$stamp = @$object->aliroCacheTimer;
				if ((time() - abs($stamp)) <= $time_limit) $result = $stamp > 0 ? $object : @$object->aliroCacheData;
			}
			// if ($object AND $timer) echo "<br />Loaded $class in ".$timer->getElapsed().' secs';
		}
		return $result;
	}

	// Worked but slightly slower than using file system
	/*
	private function memStore ($string, $name) {
		$size = strlen($name);
		if ($mop = $this->memGetToken($name, $size+8)) {
			return shmop_write($mop, str_pad((string) $size, 8, '0', STR_PAD_LEFT).$string, $size+8);
		}
		else return false;
	}

	private function memRetrieve ($name) {
		if ($mop = $this->memGetToken($name)) {
			return shmop_read($mop, 8, intval(shmop_read($mop, 0, 8)));
		}
		return null;
	}
	
	private function memGetToken ($name, $minsize=0) {
		if (function_exists('ftok') AND function_exists('shmop_open')) {
			$id = ftok($name, 'R');
			$mop = isset(self::$mops[$id]) ? self::$mops[$id] : (self::$mops[$id] = @shmop_open($id, 'w', 0600, 0));
			if ($mop) {
				if ($minsize <= shmop_size($mop)) return $mop;
				shmop_delete($mop);
			}
			return @shmop_open($id, 'c', 0600, $minsize+128);
		}
		return false;
	}
	*/
}

class aliroSingletonObjectCache extends aliroBasicCache {
	protected static $instance = null;
	protected $timeout = _ALIRO_OBJECT_CACHE_TIME_LIMIT;
	protected $sizelimit = _ALIRO_OBJECT_CACHE_SIZE_LIMIT;

	public static function getInstance () {
	    return (self::$instance instanceof self) ? self::$instance : (self::$instance = new self());
	}

	protected function getCachePath ($name) {
		return $this->getBasePath().'singleton/'.$name;
	}

	public function delete () {
		$classes = func_get_args();
		clearstatcache();
		foreach ($classes as $class) {
			$cachepath = $this->getCachePath($class);
			if (file_exists($cachepath)) @unlink($cachepath);
		}
	}

	public function deleteByExtension ($type) {
		$caches = array (
		'component' => 'aliroComponentHandler',
		'module' => 'aliroModuleHandler',
		'mambot' => 'aliroMambotHandler'
		);
		if (isset($caches[$type])) $this->delete($caches[$type]);
	}

}
