<?php

/**
*
* This the Text Component, designed to be installed into Aliro to store straightforward articles that do not need to be in folders, or use access control or anything fancy.
*
* Copyright in this edition belongs to Martin Brampton
* Email - counterpoint@aliro.org
* Web - http://www.aliro.org
*
* Information about Aliro can be found at http://www.aliro.org
*
*/

class sef_text {
	private static $instance = null;
	private $titles = array();
	private $queries = '';

	private function __construct () {
		// Do whatever setup is required
		$cache = new aliroCache('com_text');
		$this->titles = $cache->get('sef_ext');
		if (empty($this->titles)) {
			$texts = aliroDatabase::getInstance()->doSQLget("SELECT id, headline FROM #__simple_text");
			$sef = aliroSEF::getInstance();
			foreach ($texts as &$text) $this->titles[$text->id] = $sef->nameForURL($text->headline);
			$cache->save($this->titles);
		}
	}

	public static function getInstance () {
		return self::$instance ? self::$instance : (self::$instance = new self());
	}

	/**
	* Creates the SEF advance URL out of the Mambo request
	* Input: $string, string, The request URL (index.php?option=com_example&Itemid=$Itemid)
	* Output: $sefstring, string, SEF advance URL ($var1/$var2/)
	**/
	public function create ($string, $lowercase, $uniqueid, $maptags) {
		// $string == "index.php?option=com_example&var1=$var1&var2=$var2"
		$string = substr(strstr($string, '?'),1);
		parse_str($string,$params);
		unset($params['option']);
		$sefstring = '';
		if (isset($params['task'])) {
			if ('display' != $params['task']) $sefstring .= $params['task'].'/';
			unset($params['task']);
		}
		if (isset($params['id']) AND isset($this->titles[$params['id']])) {
			$headline = $this->titles[$params['id']];
			$sefstring .= ($lowercase ? strtolower($headline) : $headline).($uniqueid ? ':'.$params['id'] : '').'/';
			unset($params['id']);
		}
		if (isset($params['blog']) AND 'yes' == $params['blog']) {
			$sefstring .= 'blog/';
			unset($params['blog']);
		}
		foreach ($params as $property=>$value) $sefstring .= $property.','.$value.'/';
		return $sefstring;
	}

	/**
	* Reverts to the Aliro query string out of the SEF advance URL
	* Input:
	*    $url_array, array, The SEF advance URL split in arrays (first custom virtual directory beginning at $pos+1)
	*    $pos, int, The position of the first virtual directory (component)
	* Output: $QUERY_STRING, string, Mambo query string (&var1=$var1&var2=$var2)
	*    Note that this will be added to already defined first part (option=com_example&Itemid=$Itemid)
	**/
	public function revert ($url_array, $pos, $maptags) {
		// define all variables you pass as globals - not required for Remository - uses super globals
 		// Examine the SEF advance URL and extract the variables building the query string
		$this->queries = '';
		if (($pos+1) < count($url_array)) {
			$maybe = $url_array[$pos];
			if (in_array($maybe, array('display','inlist','taglist','multiple'))) {
				$this->addItem('task', $maybe);
				$pos++;
			}
		}
		for ($i = $pos+2; $i < count($url_array); $i++) {
			$item = $url_array[$i];
			if (!$this->itemFound($item)) {
				$cpos = strrpos($item, ':');
				if ($cpos AND is_numeric(substr($item,$cpos))) $this->addItem('id', substr($item,$cpos));
				else {
					foreach ($this->titles as $id=>$title) if (0 == strcasecmp($title, $item)) {
						$this->addItem('id', $id);
						break;
					}
				}
			}
		}
		return $this->queries;
	}

	private function itemFound ($item) {
		if ('blog' == $item) $this->addItem ('blog', 'yes');
		elseif ('tags,' == substr($item,0,5)) $this->addItem('tags', substr($item,5));
		elseif ('ids,' == substr($item,0,4)) $this->addItem('ids', substr($item,4));
	}
	
	private function addItem ($name, $value) {
		$this->queries .= '&'.$name.'='.$value;
		$_REQUEST[$name] = $_GET[$name] = $value;
	}
}