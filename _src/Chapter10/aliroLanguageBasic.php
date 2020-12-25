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
 * The aliroLanguageBasic class is a relatively simple class for carrying out 
 * operations on language information.  Less frequently used methods are in
 * aliroLanguageExtended, which is an extension of this class.
*/

// It is not intended that addons should extend this class, but it is extended within Aliro
class aliroLanguageBasic {
	protected $base_charset = 'utf-8';
    protected $codesets = array();
    protected $xmlfile = '';
    protected $convertor = null;
    
    public $isValid = false;

    public $name = '';
    public $path = '';
    public $version = '2.0';
    public $title = '';
    public $description = '';
    public $creationdate = '';
    public $author = '';
    public $authorurl = '';
    public $authoremail = '';
    public $copyright = '';
    public $license = '';
    public $territory = '';
    public $text_direction = '';
    public $date_format = '';
    public $iso639 = '';
    public $iso3166_2 = '';
    public $iso3166_3 = '';
    public $locale = '';
    public $charset = '';
    public $plural_form = array();
    public $days = array('sun'=>'','mon'=>'','tue'=>'','wed'=>'','thu'=>'','fri'=>'','sat'=>'');
    public $months = array('jan'=>'','feb'=>'','mar'=>'','apr'=>'','may'=>'','jun'=>'','jul'=>'','aug'=>'','sep'=>'','oct'=>'','nov'=>'','dec'=>'');
    public $files = array();

    public function __construct($lang, $path=null, $load_catalogs=false) {
        $this->name = $lang;
        $this->path = $path ? $path : _ALIRO_CLASS_BASE.'/language/';
        $this->xmlfile = $this->path.$this->name.'/'.$this->name.'.xml';
        if (strpos($this->name, '..')) trigger_error(T_('Language name contains illegal ".."'));
        elseif ($this->load($load_catalogs)) $this->isValid = true;
	}
	
	public function setCharset ($code) {
		$exclude = array('xmlfile', 'path', 'files');
		if ($this->base_charset != $code) {
			if ($this->validCharset($code)) {
				$this->charset = $code;
				foreach (get_object_vars($this) as $name=>$value) if (!in_array($name, $exclude)) $this->$name = $this->changeCharset($value);
			}
			else $this->isValid = false;
		}
	}

    public function validCharset ($code) {
    	return in_array($code, $this->codesets);
    }

	public function startGettext () {
		$core = aliroCore::getInstance();
        $gettext =PHPGettext::getInstance();
        $gettext->debug = intval($core->getCfg('locale_debug'));
        $gettext->has_gettext = intval($core->getCfg('locale_use_gettext'));
        $gettext->setlocale($this->name, $this->getSystemLocale());
        $gettext->bindtextdomain($this->name, _ALIRO_CLASS_BASE.'/language');
        $gettext->bind_textdomain_codeset($this->name, $this->charset);
        $gettext->textdomain($this->name);
	}

    public function getDate($format = null, $timestamp = null) {
        if (is_null($format)) $format = $this->date_format;
        if (is_null($timestamp)) $timestamp = time();
        $days = array_values($this->days);
        $months = array_values($this->months);
        $date = preg_replace('/%[aA]/', $days[(int)strftime('%w', $timestamp)], $format);
        $date = preg_replace('/%[bB]/', $months[(int)strftime('%m', $timestamp)-1], $date);
        return strftime($date, $timestamp);
    }

    public function formatDate ($time=null, $format=null) {
        return $this->getDate($format, ($time ? strtotime($time) : time()));
    }

    public function getDateFormat() {
        return $this->date_format;
    }

    /*
    public function getUntranslatedFiles () {
    	$dir = new aliroDirectory ($this->path.$this->name);
    	return $dir->listFiles('po$', 'file', false, true);
    }
    */

    // These are protected functions, used in aliroLanguageExtended
    protected function getFileName() {
        return $this->iso639.(2 == strlen($this->iso3166_2) ? '-'.$this->iso3166_2 : '');
    }

	protected function iconvert ($fromcharset,$tocharset,$source) {
		if (strtolower($fromcharset)==strtolower($tocharset)) return $source;
		if (!is_object($this->convertor)) $this->convertor = new ConvertCharset();
        return $this->convertor->Convert($source, $fromcharset, $tocharset, false);
    }

    // All private functions from here on
    public function changeCharset ($value) {
    	if (is_array($value)) foreach ($value as &$element) $element = $this->changeCharset($element);
    	if (is_string($value)) $value = $this->iconvert($this->base_charset, $this->charset, $value);
    	return $value;
    }
    
    private function getSystemLocale(){
        if (substr(strtoupper(PHP_OS), 0, 3) == 'WIN') return strtolower($this->title).($this->iso3166_3?'_'.strtolower($this->iso3166_3):'');
        else return $this->locale;
    }
    
    private function load ($load_catalogs = false) {
    	if (!is_readable($this->xmlfile)) return false;
		try {
			$xmlobject = new aliroXML;
			$xmlobject->loadFile($this->xmlfile);
			$this->extprops ($xmlobject);
			$this->locale($xmlobject);
			$this->plural($xmlobject);
			$this->codesets($xmlobject);
			$this->dayget($xmlobject);
			$this->monthget($xmlobject);
			if ($load_catalogs) $this->fileget($xmlobject);
			$this->date_format = (string) $xmlobject->getXML('locale->date_format');
		}
		catch (aliroXMLException $exception) {
			$message = $exception->getMessage();
			var_dump($this->name);
			aliroErrorRecorder::getInstance()->recordError($message, $message, $message, $exception);
			die($message);
		}
		return true;
    }

	private function extprops ($xobj) {
		$extension = new aliroExtension;
		$extension->populateFromXML($xobj);
		foreach (get_object_vars($extension) as $key=>$value) if (isset($this->$key)) $this->$key = $value;
	}

	private function locale ($xobj) {
		$attrs = array('name', 'title','territory','locale','text_direction','iso639','iso3166_2','iso3166_3','charset');
		foreach ($attrs as $attr) $this->$attr = (string) $xobj->getXML("locale->[$attr]");
	}

	private function plural ($xobj) {
		$this->plural_form = array();
		$attrs = array('nplurals', 'plural','expression');
		foreach ($attrs as $attr) $this->plural_form[$attr] = (string) $xobj->getXML("locale->plural_form->[$attr]");
	}

	private function codesets ($xobj) {
		$this->codesets = array();
		$sets = $xobj->getXML('locale->codesets->charset');
		foreach ($sets as $set) $this->codesets[] = (string) $set;
		$this->codesets = array_unique($this->codesets);
	}

	private function dayget ($xobj) {
		foreach (array_keys($this->days) as $day) {
			$this->days[$day] = (string) $xobj->getXML("locale->days->[$day]");
		}
	}
	
	private function monthget ($xobj) {
		foreach (array_keys($this->months) as $month) {
			$this->months[$month] = (string) $xobj->getXML("locale->months->[$month]");
		}
	}

	private function fileget ($xobj) {
		$this->files = array();
		foreach ($xobj->getXML('files->filename') as $file) if ($new = $this->onefile($file)) $this->files[] = $new;
	}
	
	private function onefile ($file) {
		$newfile['filename'] = (string)$file;
		// Ignore where domain not set
		$info = array('domain','strings','translated','fuzzy','percent','filetype');
		foreach ($info as $fi) $newfile[$fi] = (string) $file[$fi];
		return empty($newfile['domain']) ? null : $newfile;
	}

}