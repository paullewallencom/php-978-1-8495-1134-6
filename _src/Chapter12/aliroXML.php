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
 * aliroXMLException is the exception class used within Aliro XML handling
 *
 * aliroXML is the class that wraps PHP SimpleXML to provide easier access
 * to XML documents, including error handling.
 *
 */

class aliroXMLException extends Exception {

	public function __construct ($message) {
		parent::__construct($message, 0);
	}

}

class aliroXML {
	protected $xmlobject = null;
	protected $maintag = '';
	protected $valid = true;
	
	public function __construct ($xmlobject=null) {
		if ($xmlobject) $this->xmlobject = $xmlobject;
	}

	public function __get ($property) {
		if (is_null($this->xmlobject) OR is_null($result = $this->xmlobject->$property)) return null;
		return $result;
	}

	public function baseAttribute ($attribute) {
		return (string) $this->xmlobject[$attribute];
	}

	public function loadFile ($xmlfile, $attribs=0) {
		if (!file_exists($xmlfile)) throw new aliroXMLException(sprintf(T_('Requested XML file %s does not exist'), $xmlfile));
		if (!is_readable($xmlfile)) throw new aliroXMLException(sprintf(T_('Requested XML file %s is not readable'), $xmlfile));
		$string = file_get_contents($xmlfile);
		return $this->loadString($string, $attribs);
	}

	public function loadString ($xmlstring, $attribs=0) {
		$ampencode = '/(&(?!(#[0-9]{1,5};))(?!([0-9a-zA-Z]{1,10};)))/';
		$xmlstring = preg_replace($ampencode, '&amp;', $xmlstring);
		$tag = preg_match('/(<(?!\?)(?!\!)[^> ]*)/', $xmlstring, $matches);
		if ($tag) $this->maintag = substr($matches[0],1);
		else throw new aliroXMLException(T_('XML Handler cannot find main tag'));
		$filename = ('install' == $this->maintag) ? 'josinstall' : $this->maintag;
		if ('josinstall' == $filename) aliroRequest::getInstance()->setErrorMessage(T_('Installing Joomla package - may not work correctly', _ALIRO_ERROR_WARN));
		$filepath = _ALIRO_ABSOLUTE_PATH.'/xml/'.$filename.'.dtd';
		if (!file_exists($filepath)) throw new aliroXMLException(T_('XML Handler - no matching DTD'));
		$absparts = explode('/', _ALIRO_ABSOLUTE_PATH);
		$driveletter = (0 == strncasecmp(PHP_OS, 'win', 3)) ? array_shift($absparts) : '';
		$filename = $driveletter.implode('/', array_map('rawurlencode', $absparts)).'/xml/'.$filename.'.dtd';
		$href = 'file:///'.$filename;
		$xmlstring = '<?xml version="1.0" encoding="utf-8"?>'
		."<!DOCTYPE $this->maintag SYSTEM \"$href\">"
		.strstr($xmlstring, $matches[0]);
		set_error_handler(array($this, 'xmlerror'));
		$this->xmlobject = simplexml_load_string($xmlstring, 'SimpleXMLElement', LIBXML_DTDVALID);
		restore_error_handler();
		return $this->valid;
	}

	public function xmlerror ($errno, $errmsg) {
		$this->valid = false;
		$split = explode('parser error :', $errmsg);
		if (isset($split[1])) $errordetail = T_(' parser error: ').$split[1];
		else $errordetail = T_(' non-parser XML error ').$errmsg;
		throw new aliroXMLException(T_('An XML processing error occurred in class aliroXML'.$errordetail));
	}

	public function getXML ($properties) {
		$ps = explode('->', $properties);
		$obj = $this->xmlobject;
		foreach ($ps as $p) {
			if (is_null($obj)) return null;
			if ('[' == $p[0] AND ']' == substr($p,-1)) return (string)$obj[substr($p,1,-1)];
			if (is_null($obj = $obj->$p)) return null;
		}
		return $obj;
	}

	public static function attribArray ($xmlobject) {
		$result = array();
		foreach ($xmlobject->attributes() as $key=>$value) $result[$key] = (string) $value;
		return $result;
	}

	public static function elementArray ($xmlobject) {
		$result = array();
		foreach ($xmlobject as $element) $result[] = (string) $element;
		return $result;
	}
	
}