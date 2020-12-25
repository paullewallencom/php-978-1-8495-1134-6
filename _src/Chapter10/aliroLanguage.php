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
 * The aliroLanguage class is a singleton that holds information about the current language
*/

class aliroLanguage extends cachedSingleton  {
    protected static $instance = __CLASS__;

    protected $languages = array();
    protected $default_language = '';
    protected $default_charset = '';
    protected $base_charset = 'utf-8';
    protected $current_language = '';
    
    protected function __construct () {
    	$core = aliroCore::getInstance();
    	$this->default_charset = $core->getCfg('charset');
    	if (!$this->default_charset) $this->default_charset = $this->base_charset;
    	$langspec = $core->getCfg('locale');
		if (!$this->setupLanguage($langspec) AND !$this->setupLanguage('en')) trigger_error(T_('Default language specification is invalid'), E_USER_ERROR);
		define ('_ALIRO_LANGUAGE', $this->default_language);
	}
	
	protected function setupLanguage ($langspec) {
    	$language = new aliroLanguageBasic ($langspec, _ALIRO_CLASS_BASE.'/language/');
    	if ($language->isValid) {
    		$this->languages[$langspec] = $language;
	    	$this->languages[$langspec]->setCharset($this->default_charset);
	    	$this->default_language = $langspec;
	    	return true;
    	}
    	return false;
	}

	public static function getInstance () {
	    if (!is_object(self::$instance)) {
		    self::$instance = parent::getCachedSingleton(self::$instance);
		    self::$instance->checkLanguage();
	    }
	    return self::$instance;
	}
	
	private function checkLanguage () {
	    $this->current_language = empty($_REQUEST['lang']) ? $this->default_language : $_REQUEST['lang'];
	    if (!isset($this->languages[$this->current_language])) {
	    	$this->languages[$this->current_language] = new aliroLanguageBasic ($this->current_language, _ALIRO_CLASS_BASE.'/language/');
	    	$this->languages[$this->current_language]->setCharset($this->default_charset);
	    	if (!$this->languages[$this->current_language]->isValid) $this->languages[$this->current_language] = 'Invalid';
	    }
	    if (!is_object($this->languages[$this->current_language])) $this->current_language = $this->default_language;
	    define ('_ALIRO_LANGUAGE_CODE', $this->current_language);
	    aliroCore::set('locale', $this->current_language);
	    DEFINE('_ISO','charset='.$this->default_charset);
	    $dateformat = $this->languages[$this->current_language]->date_format;
	    DEFINE('_DATE_FORMAT_LC', $dateformat); //Uses PHP's strftime Command Format
	    DEFINE('_DATE_FORMAT_LC2', $dateformat);
		define ('_JOOMLA_LANGUAGE', $this->languages[$this->current_language]->iso639.'-GB');
		$this->languages[$this->current_language]->startGettext();
	}

    public function getDate($format = null, $timestamp = null) {
    	return $this->languages[$this->current_language]->getDate($format, $timestamp);
    }

    public function formatDate ($time=null, $format=null) {
        return $this->getDate($format, ($time ? strtotime($time) : time()));
    }

    public function getDateFormat() {
        return $this->languages[$this->current_language]->getDateFormat();
    }
    
    public function changeCharset ($value) {
    	return $this->base_charset == $this->default_charset ? $value : $this->languages[$this->current_language]->changeCharset($value);
    }
    
    public function validCharset ($code) {
    	return $this->languages[$this->current_language]->validCharset($code);
    }

    // Provided for Joomla extensions, not intended for Aliro extensions
    public function getBackwardLang () {
    	return aliroCore::getInstance()->getCfg('lang');
    }
}