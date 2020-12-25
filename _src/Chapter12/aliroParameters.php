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
 * aliroParameters is the class that implements objects that are held internally as
 * associative arrays, but externally as serialized, encoded strings.  The definition
 * of what a particular set of parameters consists of is normally provided as XML.
 *
 * aliroAdminParameters is used largely, but not exclusively, on the admin side to
 * create parameter groups from XML and serialized data.
 *
 */
class aliroParameters implements Iterator {
    protected $params = array();
    protected $raw = null;
	protected $parmspec = null;
	protected $current = '';

	public function __construct ($text='', $parmspecstring='') {
        $this->raw = is_null($text) ? '' : $text;
		if (!is_string($this->raw)) trigger_error (T_('Raw data for aliroParameters not a string'));
        $this->params = @unserialize($this->raw);
        if (!is_array($this->params)) $this->params = array();
		if ($this->raw AND count($this->params) == 0) trigger_error (T_('Raw data for aliroParameters was not null, but did not yield any values'));

        foreach ($this->params as &$param) $param = base64_decode($param);
		if ($parmspecstring) {
	        clearstatcache();
			$parmspecstring = aliroParameters::getParameterStringFromXMLFile($parmspecstring);
		}
	    $this->parmspec = (array) unserialize(base64_decode($parmspecstring));
		$this->loadDefaults();
    }

	public function loadXMLString ($xmlstring) {
		$parmspecstring = aliroParameters::getParameterStringFromXMLString($xmlstring);
	    $this->parmspec = (array) unserialize(base64_decode($parmspecstring));
		$this->loadDefaults();
	}

	private function loadDefaults () {
		if (!$this->raw) foreach ($this->parmspec as $aparam) {
			$name = (string) $aparam['attribs']['name'];
			$default = (string) $aparam['attribs']['default'];
			if ($name AND $default) $this->set($name, $default);
		}
	}

	// Provided only for backwards compatibililty - consider using alternative techniques
	public function getParams () {
		$pobject = new stdClass();
		$this->loadObject($pobject);
		return $pobject;
    }
	
	public function getParmSpecString () {
		return base64_encode(serialize($this->parmspec));
	}
    
    protected function paramKeyByNumber ($n) {
    	$keys = array_keys($this->params);
    	$c = count($keys);
    	return $n < 0 ? 0 : ($n < $c ? $keys[$n] : '');
    }
    
    public function rewind () {
    	$this->current = $this->paramKeyByNumber(0);
    }
    
    public function key () {
    	return $this->current;
    }
    
    public function current () {
    	return isset($this->params[$this->current]) ? $this->params[$this->current] : '';
    }
    
    public function next () {
    	$here = array_search($this->current,array_keys($this->params));
    	if (false === $here) $this->validiterator = false;
    	else $this->current = $this->paramKeyByNumber($here+1);
    }
    
    public function valid () {
    	return isset($this->params[$this->current]);
    }

	public function set( $key, $value='' ) {
        $this->params[$key] = $value;
        return $value;
    }

    public function setAll ($keyedValues) {
    	$this->params = $keyedValues;
    }
    
    public function setValues ($keyedValues) {
    	foreach ($keyedValues as $key=>$value) $this->params[$key] = $value;
    }
    
    public function loadObject (&$anobject) {
		foreach ($this->parmspec as $spec) if (isset($spec['attribs'])) {
			if (!empty($spec['attribs']['default'])) {
				$property = $spec['attribs']['name'];
				$anobject->$property = $spec['attribs']['default'];
			}
		}
        foreach ($this->params as $key=>$value) $anobject->$key = $value;
    }

    public function def( $key, $value='' ) {
        return $this->set ($key, $this->get($key, $value));
    }

    public function get( $key, $default='' ) {
        if (isset($this->params[$key])) return $this->params[$key] === '' ? $default : $this->params[$key];
        else return $default;
    }

    public function __get ($property) {
    	return $this->get ($property);
	}
	
	public function __set ($property, $value) {
		return $this->set($property, $value);
	}

	public function processInput ($params) {
		$inarray = (array) $params;
		foreach ($inarray as &$param) if (ini_get('magic_quotes_gpc')) $param = stripslashes($param);
		$this->params = $inarray;
		return $this->asString();
	}
	
	public function asString () {
		$encoded = array();
		foreach ($this->params as $key=>$param) $encoded[$key] = base64_encode($param);
		return serialize($encoded);
	}
	
	public function asPost () {
		return $this->params;
	}

	public function render ($name='params') {
		if ($this->parmspec) return aliroParameterRender::renderParameters($this->parmspec, $this, $name);
		else return "<textarea name='$name' cols='40' rows='10' class='text_area'>$this->raw</textarea>";
	}

	public static function getParameterStringFromXMLFile ($filepath) {
		clearstatcache();
        if (is_readable($filepath)) {
			if (is_dir($filepath)) $parmspecstring = '';
			else {
				$xmlstring = file_get_contents($filepath);
				$parmspecstring = aliroParameters::getParameterStringFromXMLString($xmlstring);
			}
        }
		return isset($parmspecstring) ? $parmspecstring : $filepath;
	}

	public static function getParameterStringFromXMLString ($xmlstring, $asArray=false) {
   		try {
	       	$parmxml = new aliroXML();
	       	$parmxml->loadString($xmlstring);
	       	$params = $parmxml->getXML('params');
	       	$parmspecstring = aliroParameters::getParameterStringFromXMLObject($params, $asArray);
    	}
    	catch (aliroXMLException $exception) {
	   		aliroRequest::getInstance()->setErrorMessage ($exception->getMessage(), _ALIRO_ERROR_FATAL);
    		$parmspecstring = '';
    	}
		return $parmspecstring;
	}

	public static function getParameterStringFromXMLObject ($xmlobject, $asArray=false) {
		$i = 0;
		if (!empty($xmlobject)) foreach ($xmlobject->param as $parm) {
			foreach ($parm->attributes() as $name=>$value) $attribs[$name] = (string) $value;
			foreach ($parm->option as $option) {
				if ((string) $option) $optionspec[0] = (string) $option;
				foreach ($option->attributes() as $oname=>$ovalue) $optionspec[$oname] = (string) $ovalue;
				if (isset($optionspec)) {
					$parmoption[] = $optionspec;
					unset($optionspec);
				}
			}
			if (isset($attribs)) {
				$allparms[$i]['attribs'] = $attribs;
				if (isset($parmoption)) $allparms[$i]['options'] = $parmoption;
				$i++;
				unset($attribs, $parmoption);
			}
		}
		$parmspec = isset($allparms) ? $allparms : array();
		return $asArray ? $parmspec : base64_encode(serialize($parmspec));
	}
}

class mosParameters extends aliroParameters {
	// Really just an alias for backwards compatibility
}

class mosAdminParameters extends aliroParameters {

	// Just an alias for aliroParameters

}

// Appears not to be used - certainly not used in Aliro

class mosSpecialAdminParameters extends aliroParameters {

	function __construct ($name, $version='') {
	    $database = aliroDatabase::getInstance();
	    $sql = "SELECT * FROM #__parameters WHERE param_name='$name'";
	    if ($version) $sql .= " AND param_version='$version'";
	    $database->setQuery($sql);
	    $parameters = $database->loadObjectList();
	    if ($parameters) $parameters = $parameters[0];
	    parent::__construct($parameters->params, aliroCore::get('mosConfig_absolute_path').'/parameters/'.$parameters->param_file);
	}
}