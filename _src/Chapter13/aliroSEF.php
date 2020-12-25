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
 * aliroUnAccent converts accented leters to their unaccented equivalent
 *
 * aliroSEF is the Search Engine Friendly URI conversion class
 */


class aliroUnAccent {

	// private vars
	private static $instance = null;
	private $tranmap = array();

	// private function
	private function aliroUnaccent () {

	$this->tranmap = array(
      "\xC3\x80" => "A",   "\xC3\x81" => "A",   "\xC3\x82" => "A",   "\xC3\x83" => "A",
      "\xC3\x84" => "A",   "\xC3\x85" => "A",   "\xC3\x86" => "AE",  "\xC3\x87" => "C",
      "\xC3\x88" => "E",   "\xC3\x89" => "E",   "\xC3\x8A" => "E",   "\xC3\x8B" => "E",
      "\xC3\x8C" => "I",   "\xC3\x8D" => "I",   "\xC3\x8E" => "I",   "\xC3\x8F" => "I",
      "\xC3\x90" => "D",   "\xC3\x91" => "N",   "\xC3\x92" => "O",   "\xC3\x93" => "O",
      "\xC3\x94" => "O",   "\xC3\x95" => "O",   "\xC3\x96" => "O",   "\xC3\x98" => "O",
      "\xC3\x99" => "U",   "\xC3\x9A" => "U",   "\xC3\x9B" => "U",   "\xC3\x9C" => "U",
      "\xC3\x9D" => "Y",   "\xC3\x9E" => "P",   "\xC3\x9F" => "ss",
      "\xC3\xA0" => "a",   "\xC3\xA1" => "a",   "\xC3\xA2" => "a",   "\xC3\xA3" => "a",
      "\xC3\xA4" => "a",   "\xC3\xA5" => "a",   "\xC3\xA6" => "ae",  "\xC3\xA7" => "c",
      "\xC3\xA8" => "e",   "\xC3\xA9" => "e",   "\xC3\xAA" => "e",   "\xC3\xAB" => "e",
      "\xC3\xAC" => "i",   "\xC3\xAD" => "i",   "\xC3\xAE" => "i",   "\xC3\xAF" => "i",
      "\xC3\xB0" => "o",   "\xC3\xB1" => "n",   "\xC3\xB2" => "o",   "\xC3\xB3" => "o",
      "\xC3\xB4" => "o",   "\xC3\xB5" => "o",   "\xC3\xB6" => "o",   "\xC3\xB8" => "o",
      "\xC3\xB9" => "u",   "\xC3\xBA" => "u",   "\xC3\xBB" => "u",   "\xC3\xBC" => "u",
      "\xC3\xBD" => "y",   "\xC3\xBE" => "p",   "\xC3\xBF" => "y",
      "\xC4\x80" => "A",   "\xC4\x81" => "a",   "\xC4\x82" => "A",   "\xC4\x83" => "a",
      "\xC4\x84" => "A",   "\xC4\x85" => "a",   "\xC4\x86" => "C",   "\xC4\x87" => "c",
      "\xC4\x88" => "C",   "\xC4\x89" => "c",   "\xC4\x8A" => "C",   "\xC4\x8B" => "c",
      "\xC4\x8C" => "C",   "\xC4\x8D" => "c",   "\xC4\x8E" => "D",   "\xC4\x8F" => "d",
      "\xC4\x90" => "D",   "\xC4\x91" => "d",   "\xC4\x92" => "E",   "\xC4\x93" => "e",
      "\xC4\x94" => "E",   "\xC4\x95" => "e",   "\xC4\x96" => "E",   "\xC4\x97" => "e",
      "\xC4\x98" => "E",   "\xC4\x99" => "e",   "\xC4\x9A" => "E",   "\xC4\x9B" => "e",
      "\xC4\x9C" => "G",   "\xC4\x9D" => "g",   "\xC4\x9E" => "G",   "\xC4\x9F" => "g",
      "\xC4\xA0" => "G",   "\xC4\xA1" => "g",   "\xC4\xA2" => "G",   "\xC4\xA3" => "g",
      "\xC4\xA4" => "H",   "\xC4\xA5" => "h",   "\xC4\xA6" => "H",   "\xC4\xA7" => "h",
      "\xC4\xA8" => "I",   "\xC4\xA9" => "i",   "\xC4\xAA" => "I",   "\xC4\xAB" => "i",
      "\xC4\xAC" => "I",   "\xC4\xAD" => "i",   "\xC4\xAE" => "I",   "\xC4\xAF" => "i",
      "\xC4\xB0" => "I",   "\xC4\xB1" => "i",   "\xC4\xB2" => "IJ",  "\xC4\xB3" => "ij",
      "\xC4\xB4" => "J",   "\xC4\xB5" => "j",   "\xC4\xB6" => "K",   "\xC4\xB7" => "k",
      "\xC4\xB8" => "k",   "\xC4\xB9" => "L",   "\xC4\xBA" => "l",   "\xC4\xBB" => "L",
      "\xC4\xBC" => "l",   "\xC4\xBD" => "L",   "\xC4\xBE" => "l",   "\xC4\xBF" => "L",
      "\xC5\x80" => "l",   "\xC5\x81" => "L",   "\xC5\x82" => "l",   "\xC5\x83" => "N",
      "\xC5\x84" => "n",   "\xC5\x85" => "N",   "\xC5\x86" => "n",   "\xC5\x87" => "N",
      "\xC5\x88" => "n",   "\xC5\x89" => "n",   "\xC5\x8A" => "N",   "\xC5\x8B" => "n",
      "\xC5\x8C" => "O",   "\xC5\x8D" => "o",   "\xC5\x8E" => "O",   "\xC5\x8F" => "o",
      "\xC5\x90" => "O",   "\xC5\x91" => "o",   "\xC5\x92" => "CE",  "\xC5\x93" => "ce",
      "\xC5\x94" => "R",   "\xC5\x95" => "r",   "\xC5\x96" => "R",   "\xC5\x97" => "r",
      "\xC5\x98" => "R",   "\xC5\x99" => "r",   "\xC5\x9A" => "S",   "\xC5\x9B" => "s",
      "\xC5\x9C" => "S",   "\xC5\x9D" => "s",   "\xC5\x9E" => "S",   "\xC5\x9F" => "s",
      "\xC5\xA0" => "S",   "\xC5\xA1" => "s",   "\xC5\xA2" => "T",   "\xC5\xA3" => "t",
      "\xC5\xA4" => "T",   "\xC5\xA5" => "t",   "\xC5\xA6" => "T",   "\xC5\xA7" => "t",
      "\xC5\xA8" => "U",   "\xC5\xA9" => "u",   "\xC5\xAA" => "U",   "\xC5\xAB" => "u",
      "\xC5\xAC" => "U",   "\xC5\xAD" => "u",   "\xC5\xAE" => "U",   "\xC5\xAF" => "u",
      "\xC5\xB0" => "U",   "\xC5\xB1" => "u",   "\xC5\xB2" => "U",   "\xC5\xB3" => "u",
      "\xC5\xB4" => "W",   "\xC5\xB5" => "w",   "\xC5\xB6" => "Y",   "\xC5\xB7" => "y",
      "\xC5\xB8" => "Y",   "\xC5\xB9" => "Z",   "\xC5\xBA" => "z",   "\xC5\xBB" => "Z",
      "\xC5\xBC" => "z",   "\xC5\xBD" => "Z",   "\xC5\xBE" => "z",   "\xC6\x8F" => "E",
      "\xC6\xA0" => "O",   "\xC6\xA1" => "o",   "\xC6\xAF" => "U",   "\xC6\xB0" => "u",
      "\xC7\x8D" => "A",   "\xC7\x8E" => "a",   "\xC7\x8F" => "I",
      "\xC7\x90" => "i",   "\xC7\x91" => "O",   "\xC7\x92" => "o",   "\xC7\x93" => "U",
      "\xC7\x94" => "u",   "\xC7\x95" => "U",   "\xC7\x96" => "u",   "\xC7\x97" => "U",
      "\xC7\x98" => "u",   "\xC7\x99" => "U",   "\xC7\x9A" => "u",   "\xC7\x9B" => "U",
      "\xC7\x9C" => "u",
      "\xC7\xBA" => "A",   "\xC7\xBB" => "a",   "\xC7\xBC" => "AE",  "\xC7\xBD" => "ae",
      "\xC7\xBE" => "O",   "\xC7\xBF" => "o",
      "\xC9\x99" => "e",

      "\xC2\x82" => ",",        // High code comma
      "\xC2\x84" => ",,",       // High code double comma
      "\xC2\x85" => "...",      // Tripple dot
      "\xC2\x88" => "^",        // High carat
      "\xC2\x91" => "\x27",     // Forward single quote
      "\xC2\x92" => "\x27",     // Reverse single quote
      "\xC2\x93" => "\x22",     // Forward double quote
      "\xC2\x94" => "\x22",     // Reverse double quote
      "\xC2\x96" => "-",        // High hyphen
      "\xC2\x97" => "--",       // Double hyphen
      "\xC2\xA6" => "|",        // Split vertical bar
      "\xC2\xAB" => "<<",       // Double less than
      "\xC2\xBB" => ">>",       // Double greater than
      "\xC2\xBC" => "1/4",      // one quarter
      "\xC2\xBD" => "1/2",      // one half
      "\xC2\xBE" => "3/4",      // three quarters

      "\xCA\xBF" => "\x27",     // c-single quote
      "\xCC\xA8" => "",         // modifier - under curve
      "\xCC\xB1" => ""          // modifier - under line
	);

	}

    public static function getInstance () {
        return self::$instance ? self::$instance : (self::$instance = new self());
    }

	public function unaccent ($utf8string) {
		return strtr($utf8string, $this->tranmap);
	}

}

class aliroSEF extends aliroFriendlyBase {
	protected static $instance = null;
	protected static $uriregex = '([0-9a-zA-Z_\-\.\~\*\(\)]*)';

	// The following are private
	private $live_site = '';
	private $rewriteprefix = '';
	private $homelink = null;
	private $home_page = false;
	private $config = null;
	private $content_tasks = array(
		'findkey',
		'view',
		'section',
		'category',
		'blogsection',
		'blogcategorymulti',
		'blogcategory',
		'archivesection',
		'archivecategory',
		'save',
		'cancel',
		'emailform',
		'emailsend',
		'vote',
		'showblogsection'
		);
	private $content_menus = array();
	private $metadata = null;

	// The following are public
	public $content_data = null;
	public $content_items = array();
	public $content_sections = array();
	public $content_categories = array();

	// The following are private
	private $SEF_SPACE;
	private $cache = null;
	private $cached = array();
	private $cacheWritten = array();
	private $cacheObject = null;
	private $requestTime = 0;
	private $doneRetrieval = false;
	private $retrieveCode = false;
	
	private $database = null;

	private function __construct () {
		$this->database = aliroDatabase::getInstance();
		$this->live_site = $this->getCfg('live_site');
		$this->requestTime = time();
		$homemenu = aliroMenuHandler::getInstance()->getHome();
		if ($homemenu) $this->homelink = $homemenu->link;

		// Use of underscore is NOT recommended, as search engines then do not see the words
		global $_SEF_SPACE;									// divide words with hyphens
		$this->SEF_SPACE = $_SEF_SPACE = "-";				// divide words with hyphens

		$this->cache =new aliroCache('aliroSEF', _ALIRO_SEF_MAX_CACHE_SIZE);
		$this->config = $this->cache->get('sefConfig');
		if (!$this->config) {
			$helper = new aliroSEFHelper();
			$this->config = $helper->getConfig($this->SEF_SPACE, $this->content_tasks);
			$this->cache->save($this->config);
		}
		$this->rewriteprefix = (!$this->config->enabled OR $this->config->url_rewrite) ? '' : '/index.php';

		$this->cached = $this->cache->get('sefDataURI');
		if (!$this->cached) {
			$this->cached['SEF'] = $this->cached['Time'] = array();
			$results = $this->database->doSQLget("SELECT u.*, m.htmltitle, m.robots, m.keywords, m.description FROM #__remosef_uri AS u LEFT JOIN #__remosef_metadata AS m ON u.id = m.id ORDER BY u.refreshed DESC LIMIT {$this->config->buffer_size}");
			foreach ($results as $result) {
				$uri = $result->uri;
				$this->cached['SEF'][$uri] = $result->sef;
				$this->cached['Time'][$uri] = $result->refreshed;
				$metadata = $this->makeMetaObject($result);
				if ($metadata) $this->cached['Meta'][$uri] = $metadata;
			}
			unset($results);
			$results = aliroCoreDatabase::getInstance()->doSQLget("SELECT id, uri, class, notemplate, nohtml FROM #__urilinks WHERE published != 0 ORDER BY LENGTH(uri) DESC");
			$this->cached['Links'] = array();
			foreach ($results as $result) {
				$prepared['id'] = $result->id;
				$prepared['class'] = $result->class;
				$prepared['notemplate'] = $result->notemplate;
				$prepared['nohtml'] = $result->nohtml;
				$uriparts = explode('/', $result->uri);
				foreach ($uriparts as $uripart) {
					$prepared['uri'][] = str_replace('*', self::$uriregex, $uripart);
				}
				$this->cached['Links'][] = $prepared;
				unset($prepared);
			}
			$this->cache->save($this->cached);
		}
	}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self());
	}
	
	private function makeMetaObject ($row) {
		foreach (array('htmltitle', 'robots', 'keywords', 'description') as $metafield) {
			if ($row->$metafield) {
				if (!isset($metadata)) $metadata = new stdClass();
				$metadata->$metafield = $row->$metafield;
			}
		} 
		return isset($metadata) ? $metadata : null;
	}
	
	public function clearCache () {
		$this->cache->clean();
	}

	public function getContentMenuInfo () {
		return aliroMenuHandler::getInstance()->getContentMenuInfo();
	}

	public function nameForURL ($string) {
		$string = aliroUnaccent::getInstance()->unaccent($string);
       	$string = str_replace($this->config->sef_name_chars, $this->config->sef_translate_chars, $string);
		$string = preg_replace("/($this->SEF_SPACE)+/", $this->SEF_SPACE, $string);
		$string = urlencode($string);
		return $string;
	}

	public function translateContentTask ($task) {
		return isset($this->config->sef_content_task[$task]) ? $this->config->sef_content_task[$task] : $task;
	}

	public function untranslateContentTask ($tr_task) {
		$task = array_search ($tr_task, $this->config->sef_content_task);
		if (!$task) $task = $tr_task;
		return in_array($task, $this->content_tasks) ? $task : null;
	}

	private function analyseStandardURI ($uri, $postfix) {
		$_SERVER['REQUEST_URI'] = $uri.$postfix;
		$mainparts = explode('?', $uri);
		if (empty($mainparts[1])) return;
		$_SERVER['QUERY_STRING'] = $mainparts[1];
		$vars = explode('&', $mainparts[1]);
		foreach ($vars as $var) {
			$parts = explode('=', $var);
			if (!empty($parts[1])) {
				$_REQUEST[$parts[0]] = $_GET[$parts[0]] = $parts[1];
			}
		}
	}
	
	private function redirect301 ($to) {
		$chandler = aliroComponentHandler::getInstance();
		$chandler->endBuffer();
		if (!isset($_SESSION['aliro_component_diagnostics'])) $_SESSION['aliro_component_diagnostics'] = '';
		$_SESSION['aliro_component_diagnostics'] .= '<br />SEF Redirect to '.$to.'<br />'.$chandler->getBuffer();
		header ($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
   		header ('Location:'.$to);
   		exit;
	}
	
	public function urilink ($parturi) {
		$rewriteprefix = ($this->config->url_rewrite) ? '' : '/index.php';
		return $this->live_site.$rewriteprefix.$parturi;
	}

	public function despatcher () {
	    $uri = @preg_replace(array_keys($this->config->sef_substitutions_in), array_values($this->config->sef_substitutions_in), $this->getURI());
	    $link = $this->getLinkByURI($uri);
	    if ($link) {
			$results = explode(',', $link['class']);
			if (2 > count($results)) $results[] = '';
			return array(trim($results[0]), trim($results[1]), $link['notemplate'], $link['nohtml'], $link['id']);
	    }
		return array('', '', '', '', 0);
	}
	
	public function isValidURI ($uri) {
		return $this->getLinkByURI($uri) ? true : false;
	}
	
	private function getLinkByURI ($uri) {
		while ($uri AND '/' == $uri[0]) $uri = substr($uri,1);
		$uriparts = explode('/', $uri);
		foreach ($this->cached['Links'] as $link) {
			$matched = true;
			foreach ($link['uri'] as $i=>$part) {
				if (!isset($uriparts[$i]) OR !($part == $uriparts[$i] OR $this->uriMatch($part, $uriparts[$i]))) {
					$matched = false;
					break;
				}
			}
			if ($matched) return $link;
		}
		return false;
	}
	
	private function uriMatch ($pattern, $actual) {
		return (0 < preg_match_all("/^$pattern$/", $actual, $matches));
	}
	
	public function sefRetrieval() {
		if ($this->doneRetrieval) return $this->retrieveCode;
		else $this->doneRetrieval = true;
	    $saveuri = $this->getURI();
	    $uri = str_replace(array('&amp;', '//'), array('&', '/'), $saveuri);
		$uri = preg_replace("/($this->SEF_SPACE)+/", $this->SEF_SPACE, $uri);
		if (!$uri OR '/' == $uri OR '/index.php' == $uri) {
			$this->home_page = true;
			return false;
		}
		if ($postfix = strrchr($uri, '#')) $uri = substr($uri,0,-strlen($postfix));
		else $postfix = '';
		// if ($this->config->underscore AND $this->SEF_SPACE == '-' AND strpos($uri,'_') !== false) $uri = str_replace('_', '-', $uri);
	    $exactback = array_search($uri, $this->config->sef_substitutions_exact);
	    if (false === $exactback AND '/' != substr($uri,-1)) {
	    	$exactback = array_search($uri.'/', $this->config->sef_substitutions_exact);
	    	if (false !== $exactback) $this->redirect301($this->live_site.$this->rewriteprefix.$uri.'/');
	    }
	    if (false !== $exactback) {
	    	$this->analyseStandardURI($exactback, $postfix);
	    	return false;
	    }
	    $uri = @preg_replace(array_keys($this->config->sef_substitutions_in), array_values($this->config->sef_substitutions_in), $uri);
		if ($indexloc = strpos($uri, 'index.php?')) {
			if ($_SERVER['REQUEST_METHOD'] == 'GET') {
				$sefagain = substr($this->sefRelToAbs(substr($uri,$indexloc), false),strlen($this->live_site));
				if ($saveuri != $sefagain) $this->redirect301($this->live_site.$this->rewriteprefix.$sefagain);
			}
			return false;
		}
		elseif (false !== strpos($uri,'index2.php') OR false !== strpos($uri,'index3.php')) return false;
		// if ($this->config->underscore) $uri = str_replace('_', $this->SEF_SPACE, $uri);
		$ignores = array('/forum');
		$use_db = true;
		foreach ($ignores as $ignore) if ($ignore == substr($uri,0,strlen($ignore))) {
			$use_db = false;
		}
		if ($use_db) {
			$retrieved = $this->retrieveURI ($uri);
			if (!$retrieved AND '/' != substr($uri,-1)) {
				$retrieved = $this->retrieveURI($uri.'/');
				if ($retrieved) $this->redirect301($this->live_site.$this->rewriteprefix.$uri.'/');
			}
		}
		else $retrieved = false;
		if (!$retrieved) {
			$helper = new aliroSEFHelper();
			$retrieved = $helper->basicRetrieve($uri, $this->config, $this, $this->live_site, $this->SEF_SPACE);
			// if ($retrieved AND $use_db) trigger_error('Had to invoke SEF basicRetrieve: '.$uri.' -> '.$retrieved);
		}

		if ($retrieved) {
			$retrieved = 'index.php?'.$retrieved;
			$sefagain = substr($this->sefRelToAbs($retrieved),strlen($this->live_site)+strlen($this->rewriteprefix));
			if ($saveuri != $sefagain AND $_SERVER['REQUEST_METHOD'] == 'GET') $this->redirect301($sefagain);
    		$this->analyseStandardURI($retrieved, $postfix);
			return false;
		}
		else {
			$this->retrieveCode = true;
			return true;
		}
	}
	
	private function getURI () {
		static $uri = null;
		if ($uri) return $uri;
		$uri = $_SERVER['REQUEST_URI'];
		if (preg_match('/(\b)GLOBALS|_REQUEST|_SERVER|_ENV|_COOKIE|_GET|_POST|_FILES|_SESSION(\b)/i', @$_SERVER['REQUEST_URI']) > 0) {
			die('Invalid Request');
		}
		$splituri = preg_split('#/index[0-9]?\.php#', $_SERVER['PHP_SELF']);
		//if (!empty($splituri[1])) return $uri = substr($splituri[1],1);
		if (!empty($splituri[1])) return $uri = $splituri[1];
		$sublength = aliroCore::getInstance()->getSubLen();
		return $uri = 1 < $sublength ? substr($uri, $sublength) : $uri;
	}
	
	private function retrieveURI ($sef) {
		$retrieved = array_search($sef, $this->cached['SEF']);
		if ($retrieved) {
			$this->metadata = isset($this->cached['Meta'][$retrieved]) ? $this->cached['Meta'][$retrieved] : null;
			return $retrieved;
		}
		$coded_sef = $this->database->getEscaped($sef);
		$this->database->setQuery("SELECT u.uri, m.htmltitle, m.robots, m.keywords, m.description FROM #__remosef_uri AS u LEFT JOIN #__remosef_metadata AS m ON u.id = m.id WHERE sef_crc = CRC32('$coded_sef') AND sef='$coded_sef'");
		$this->database->loadObject($sefdata);
		if ($sefdata) {
			$this->metadata = $this->makeMetaObject($sefdata);
			return $sefdata->uri;
		}
		else return false;
	}

	// Not intended for general use - public only for use by helper class
	public function invoke_plugin ($i, $method, $parm1, $parm2=0) {
		error_reporting(E_ALL);
		require_once($this->config->custom_PHP[$i]);
		$classname = 'sef_'.$this->config->custom_short[$i];
		$compname = 'com_'.$this->config->custom_short[$i];
		$maptags = isset($this->config->component_details[$compname]) ? $this->config->component_details[$compname] : array();
		if (method_exists($classname, 'getInstance')) {
			$plugin = call_user_func(array($classname, 'getInstance'));
			if ('create' == $method) return $plugin->create($parm1, $this->config->lower_case, $this->config->unique_id, $maptags);
			else return $plugin->revert($parm1, $parm2, $maptags);
		}
		else {
			$callplugin = array($classname, $method);
			if ('create' == $method) return call_user_func ($callplugin, $parm1, $this->config->lower_case, $this->config->unique_id, $maptags);
			else return call_user_func ($callplugin, $parm1, $parm2, $maptags);
		}
		error_reporting(E_ALL|E_STRICT);
	}

	private function parse ($string, &$parms) {
		$parms = array();
		$parts = explode('&', $string);
		foreach ($parts as $part) {
			$assigns = explode('=', $part);
			if (count($assigns) == 2) $parms[$assigns[0]] = $assigns[1];
		}
	}

    public function getHead($title, $metatags, $customtags) {
		$head = $found = array();
		$block['title'] = 1;
		$sitename = $this->getCfg('sitename');
		if ($this->home_page) $extratitle = $this->config->home_title;
		elseif (empty($this->metadata->htmltitle)) {
			if (strlen($title) > strlen($sitename)) $extratitle = substr($title, 0, -(strlen($sitename)+3));
			else $extratitle = '';
		}
		else $extratitle = htmlspecialchars($this->metadata->htmltitle, ENT_QUOTES, 'UTF-8');
		if ($extratitle) {
			if (strlen($extratitle) + strlen($sitename) < 60) $extratitle .= ' '.$this->config->title_separator.' '.$sitename;
		}
		else $extratitle = $sitename;
		$head[] = '<title>' . $extratitle . '</title>';
		
		if (!empty($this->metadata->description)) {
			$head[] = $this->makeMeta('description', htmlspecialchars($this->metadata->description, ENT_QUOTES, 'UTF-8'));
			$block['description'] = 1;
		}
		if (!empty($this->metadata->keywords)) {
			$head[] = $this->makeMeta('keywords', htmlspecialchars($this->metadata->keywords, ENT_QUOTES, 'UTF-8'));
			$block['keywords'] = 1;
		}
		if (!empty($this->metadata->robots)) {
			$head[] = $this->makeMeta('robots', htmlspecialchars($this->metadata->robots, ENT_QUOTES, 'UTF-8'));
			$block['robots'] = 1;
		}

        foreach ($metatags as $name=>$meta) {
			if (isset($block[$name]) OR empty($meta[0])) continue;
			$found[$name] = 1;
            if ($meta[1]) $head[] = $meta[1];
			$head[] = $this->makeMeta ($name, $meta[0]);
            if ($meta[2]) $head[] = $meta[2];
        }

		if (empty($block['description']) AND empty($found['description'])) $head[] = $this->makeMeta('description', htmlspecialchars($this->getCfg('MetaDesc'), ENT_QUOTES, 'UTF-8'));
		if (empty($block['keywords']) AND empty($found['keywords'])) $head[] = $this->makeMeta('keywords', htmlspecialchars($this->getCfg('MetaKeys'), ENT_QUOTES, 'UTF-8'));
		if (empty($block['robots']) AND empty($found['robots'])) $head[] = $this->makeMeta('robots', $this->config->default_robots);

        foreach ($customtags as $html) $head[] = $html;
        return implode( "\n", $head )."\n";
    }

	private function makeMeta ($name, $value) {
		return <<<META_DATA
<meta name="$name" content="$value" />
META_DATA;

	}
	
	public function sefComponentName ($cname) {
		$i = array_search($cname,$this->config->custom_code);
		return ($i !== false AND $i !== null) ? $this->config->custom_name[$i] : $cname;
	}

	public function sefRelToAbs ($string, $externalcall=true) {
		if ('index.php' == $string) return $this->live_site.'/';
		if (strtolower(substr($string,0,9)) != 'index.php' OR preg_match('/^(([^:\/?#]+):)/',$string)) return $string;
		$string = str_replace('&amp;', '&', $string);
		if ($postfix = strrchr($string, '#')) $string = substr($string,0,-strlen($postfix));
		else $postfix = '';
		$clean_string = preg_replace('/\&Itemid=[0-9]*/', '', $string);
		if ($clean_string == $this->homelink) return $this->live_site.'/'.$postfix;
		if (!$this->config->enabled) return $this->live_site.'/'.($externalcall ? str_replace( '&', '&amp;', $clean_string ) : $clean_string).$postfix;
		$string = substr($clean_string,10);
		if (isset($this->config->sef_substitutions_exact['/'.$clean_string])) {
			return $this->live_site.$this->rewriteprefix.$this->config->sef_substitutions_exact['/'.$clean_string].$postfix;
		}
		if (isset($this->cached['SEF'][$clean_string]) AND (time() - $this->config->cachedTime[$clean_string]) < $this->config->cache_time) return $this->live_site.$this->cached['SEF'][$clean_string].$postfix;
		$oktasks = true;
		$option = $task = '';
		$this->parse($string, $params);
		foreach ($params as $key=>$value) {
			$lowkey = strtolower($key);
			$lowvalue = strtolower($value);
			$unset = true;
			switch ($lowkey) {
				case 'option':
				    $option = $lowvalue;
				    break;
				case 'task':
				    $task = $value;
					if ($lowvalue == 'new' OR $lowvalue == 'edit') $oktasks = false;
					break;
				default:
					$check_params[$lowkey] = $key;
					$unset = false;
			}
			if ($unset) unset($params[$key]);
		}
		// Process content items
		if (($option == 'com_content' OR $option == 'content') AND $oktasks) {
			/*
			Content
			index.php?option=com_content&task=$task&sectionid=$sectionid&id=$id&Itemid=$Itemid&limit=$limit&limitstart=$limitstart
			*/
			$content_sef = _ALIRO_CLASS_BASE.'/components/com_content/sef_ext.php';
			if (file_exists($content_sef)) {
				require_once($content_sef);
				$result = sef_content::create($task, $params, $this->config->lower_case, $this->config->unique_id);
				return $this->live_site.$this->rewriteprefix.$this->outSubstitution($string, $result, $externalcall).$postfix;
			}
			$keys = array('sectionid', 'id', 'itemid', 'limit', 'limitstart', 'year', 'month', 'module', 'lang');
			$result = '/content/'.$task.'/';
			foreach ($keys as $key) {
				if (isset($check_params[$key])) {
					$pkey = $check_params[$key];
					$result .= $params[$pkey].'/';
				}
			}
			return $this->live_site.$this->rewriteprefix.$this->outSubstitution($string, $result, $externalcall).$postfix;
		}
		// Process customised components
		$i = array_search($option,$this->config->custom_code);
		if ($i !== false AND $i !== null) {
			if ($this->config->custom_PHP[$i] AND file_exists($this->config->custom_PHP[$i])) {
				$result = $this->invoke_plugin ($i, 'create', $clean_string);
			}
			else $result = $this->componentDetails($params,$task);
			$cname = str_replace(' ', $this->SEF_SPACE, $this->config->custom_name[$i]);
			$result = '/'.($this->config->lower_case ? strtolower($cname) : $cname).'/'.$result;
			return $this->live_site.$this->rewriteprefix.$this->outSubstitution($string, $result, $externalcall).$postfix;
		}
		// Process ordinary components
		if (strpos($option,'com_')===0 AND $oktasks) {
			$result = "/component/option,$option/".$this->componentDetails($params,$task);
			return $this->live_site.$this->rewriteprefix.$this->outSubstitution($string, $result, $externalcall).$postfix;
		}
		// Anything else is returned as received, except it is guaranteed that & will be &amp;
		return $this->live_site.'/'.($externalcall ? str_replace( '&', '&amp;', $clean_string ) : $clean_string).$postfix;
	}

	private function outSubstitution ($inuri, $outuri, $external) {
		$outuri = preg_replace("/($this->SEF_SPACE)+/", $this->SEF_SPACE, $outuri);		
		$finishedurl = trim(@preg_replace(array_keys($this->config->sef_substitutions_out), array_values($this->config->sef_substitutions_out), $outuri));
		if (!$external) return $finishedurl;
		if (isset($this->cached['SEF'][$inuri]) AND $finishedurl == $this->cached['SEF'][$inuri] AND ($this->requestTime - $this->cached['Time'][$inuri] < (int) $this->config->cache_time)) return $finishedurl;
		if (!isset($this->cached['SEF'][$inuri]) OR $this->cached['SEF'][$inuri] != $finishedurl OR (($this->requestTime - $this->cached['Time'][$inuri]) > (int) ($this->config->cache_time/5))) {
			$inuri = trim($inuri);
			$this->cached['SEF'][$inuri] = $finishedurl;
			$this->cached['Time'][$inuri] = $this->requestTime;
		}
		return $finishedurl;
	}
	
	private function isTemporary ($uri) {
		// Should be elaborated to be configurable by admin
		return (false !== strpos('option=com_remository', $uri) AND false !== strpos('chk=', $uri));
	}
	
	public function saveCache () {
		$sefcount = 0;
		$session = aliroSession::getSession();
		foreach ($this->cached['Time'] as $curi=>$timestamp) if ($this->requestTime == $timestamp) {
			$sefcount++;
			$uri = $this->database->getEscaped($curi);
			$sef = $this->database->getEscaped($this->cached['SEF'][$curi]);
			$this->database->doSQL("UPDATE #__remosef_uri SET sef = '$sef', sef_crc = CRC32('$sef'), refreshed = $this->requestTime, marker = 1 - marker, ipaddress = '$session->ipaddress' WHERE uri_crc = CRC32('$uri') AND uri = '$uri'");
			$counter = $this->database->getAffectedRows();
			if (1 != $counter) {
				if (1 < $counter) $this->database->doSQL("DELETE FROM #__remosef_uri WHERE uri_crc = CRC32('$uri') AND uri = '$uri'");
				$shortterm = $this->isTemporary($uri) ? '1' : '0';
				$this->database->doSQL("INSERT INTO #__remosef_uri (uri, uri_crc, sef, sef_crc, shortterm, refreshed, ipaddress) VALUES ('$uri', CRC32('$uri'), '$sef', CRC32('$sef'), $shortterm, $this->requestTime, '$session->ipaddress')");
			}
		}
		if ($sefcount AND !($totalsaved = $this->cache->save($this->cached, 'sefDataURI', false))) {
			$values = array_values($this->cached['Time']);
			sort($values, SORT_NUMERIC);
			$median = $values[(int)count($values)/2];
			foreach ($this->cached['Time'] as $uri=>$timestamp) if ($timestamp < $median) {
				unset($this->cached['Time'][$uri], $this->cached['SEF'][$uri]);
			}
			if (!$this->cache->save($this->cached, 'sefDataURI', false)) {
				trigger_error(sprintf(T_('SEF Cache save still failed after reduction, before %s, after %s, median %s'), $sefcount, count($this->cached['Time'][$uri]), $median));
			}
			if (42 == mt_rand(0,99)) {
				$weekago = $this->requestTime - 7*24*60*60;
				$this->database->doSQL("DELETE FROM #__remosef_uri WHERE 0 != shortterm AND refreshed < $weekago");
			}
		}
	}

	private function componentDetails (&$params, $task) {
		$string = ($task ? "task,$task/" : '');
		foreach ($params as $key=>$param) {
                    $param = urlencode($param);
                    $string .= "$key,$param/";
                }
		return $string;
	}

}