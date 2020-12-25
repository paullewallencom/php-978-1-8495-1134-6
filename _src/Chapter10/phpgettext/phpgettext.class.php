<?php
/**
 * @version		0.9
 * @author      Carlos Souza
 * @copyright   Copyright (c) 2005 Carlos Souza <csouza@web-sense.net>
 * @package     PHPGettext
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		http://phpgettext.web-sense.net
 *
 *
 */
class PHPGettext {
	/**
	 * The sole instance of the class
	 */
    private static $instance = __CLASS__;
    private static $before = array ("\n", "\r");
    private static $after = array ('\n', '\r');

	/**
	 * Is gettext available externally?
	 */
    public $has_gettext = false;

    /**
     * The current locale. eg: en-GB
     */
    var $lang;
    /**
     * The singleton language object
     */
    var $language = null;
    /**
     * The current locale. eg: en-GB
     */
    var $locale;

    /**
     * The current domain
     */
    var $domain;

    /**
     * The current character set
     */
    var $charset;

    /**
     * Container for the loaded domains
     */
    var $text_domains = array();

    /**
     * The asssociative array of headers for the current domain
     */
    var $headers = array();
    /**
     * The asssociative array of messages for the current domain
     */
    var $messages = array();

    /**
     * The debugging flag
     */
    var $debug = 0;

    private function __construct () {
    	$this->has_gettext = (function_exists("gettext") AND function_exists("_"));
    	if (!$this->has_gettext) require_once(dirname(__FILE__).'/phpgettext.compat.php');
    	$this->language = aliroLanguage::getInstance();
    }

    private function __clone () {}

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self::$instance);
	}

    /**
     *
     * Set and lookup the locale from the environment variables.
     * Priority order for gettext is:
     * 1. LANGUAGE
     * 2. LC_ALL
     * 3. LC_MESSAGE
     * 4. LANG
     *
     * @return unknown
     */

    private function setVariable($variable){
        if ($this->has_gettext) $this->has_gettext = $this->has_gettext AND @putenv($variable);
    }

    public function setlocale ($lang, $locale=false) {
        $this->setvariable("LANGUAGE=$lang");
        $this->setvariable("LC_ALL=$lang");
        $this->setvariable("LC_MESSAGE=$lang");
        $this->setvariable("LANG=$lang");
        $this->lang =  $lang;
        if ($locale) $this->locale = setlocale(LC_ALL,$locale);
    }

    public function getlocale () {
        if (empty($this->locale)) {
            $langs = array( getenv('LANGUAGE'),
            getenv('LC_ALL'),
            getenv('LC_MESSAGE'),
            getenv('LANG')
            );
            foreach ($langs as $lang) if ($lang) {
                $this->locale = $lang;
                break;
            }
        }
        return $this->locale;
    }

    /**
     * debugging function
     *
     */
    private function output($message, $untranslated = false){
        switch ($this->debug)
        {
            case 2:
            $trace = debug_backtrace();
            $html = '<span style="border-bottom: thin solid %s" title="%s(%d)">T_(%s)</span>';
            $str = sprintf($html, ($untranslated ? 'red' : 'green'), str_replace('\\', '/', $trace[2]['file']), $trace[2]['line'], $message);
            break;
            case 1:
            $str    = sprintf('%sT_(%s)',$untranslated ? '!' : '', $message);
            break;
            case 0:
            default:
            $str    = $message;
            break;
        }
        return $str;
    }

    /**
     * Alias for gettext
     * will also output the result if $output = true
     */
    public function _($message, $output = false){
        if ($output) {
            echo $this->gettext($message);
            return true;
        }
        else return $this->gettext($message);
    }

    /**
     * Lookup a message in the current domain
     * returns translation if it exists or original message
     */
    public function gettext($message){
        if ($this->has_gettext) $translation = gettext($message);
        else {
			$fixupmessage = str_replace(self::$before, self::$after, addslashes($message));
           	if (!empty($this->messages[$this->domain][$fixupmessage])) {
           		$translation = $this->messages[$this->domain][$fixupmessage];
           	}
           	else $translation = isset($this->messages[$this->domain][$fixupmessage]) ? $fixupmessage : $message;
        }
        if ($this->debug) $translation = $this->output($translation, (strcmp($translation, $message) === 0));
        return $this->language->changeCharset($translation);
    }

    /**
     * Override the current domain
     * The dgettext() function allows you to override the current domain for a single message lookup.
     */
    public function dgettext ($domain, $message) {
        if (array_key_exists($domain, $this->messages) AND !empty($this->messages[$domain][$message])) {
            	$translation = $this->messages[$domain][$message];
        }
		else $translation = $message;
        if ($this->debug) $translation = $this->output($translation, (strcmp($translation, $message) === 0));
        return $this->language->changeCharset($translation);
    }

    /**
     * Plural version of gettext
     */
    public function ngettext ($msgid, $msgid_plural, $count) {
        if ($this->has_gettext) $translation = ngettext($msgid, $msgid_plural, $count);
        else {
			$msgid = str_replace(self::$before, self::$after, addslashes($msgid));
            $plural = $this->getplural($count, $this->domain);
            $original = array($msgid, $msgid_plural);
            $translation = isset($this->messages[$this->domain][$msgid][$plural]) ? $this->messages[$this->domain][$msgid][$plural] : $original[$plural];
        }
        if ($this->debug) $translation = $this->output($translation, (isset($original[$plural]) AND (strcmp($translation, $original[$plural]) === 0)));
        return $this->language->changeCharset($translation);
    }
    /**
     * Plural version of dgettext
     */
    public function dngettext ($domain, $msgid, $msgid_plural, $count) {
        $original = array($msgid, $msgid_plural);
        if ($this->has_gettext) $translation = dngettext($domain, $msgid, $msgid_plural, $count);
        else {
			$msgid = str_replace(self::$before, self::$after, addslashes($msgid));
	        $plural = $this->getplural($count, $domain);
            $translation = isset($this->messages[$domain][$msgid][$plural]) ? $this->messages[$domain][$msgid][$plural] : $original[$plural];
        }
        if ($this->debug) $translation = $this->output($translation, (strcmp($translation, $original[$this->getplural($count, $domain)]) === 0));
        return $this->language->changeCharset($translation);
    }

    /**
     * Specify the character encoding in which the messages
     * from the DOMAIN message catalog will be returned
     *
     */
    public function bind_textdomain_codeset ($domain, $charset) {
        if ($this->has_gettext) bind_textdomain_codeset($domain, $charset);
        return $this->text_domains[$domain]["charset"] = $charset;
    }

    /**
     * Sets the path for a domain
     * if gettext is unavailable, translation files will be loaded here
     *
     */
    public function bindtextdomain ($domain, $path) {
        if ($this->has_gettext) bindtextdomain($domain, $path);
        else $this->load($domain, $path);
        return $this->text_domains[$domain]["path"] = $path;
    }

    /**
     * Sets the default domain textdomain
     */
    public function textdomain ($domain = null) {
        if ($this->has_gettext) $this->domain = textdomain($domain);
        elseif (!is_null($domain)) {
            $this->domain = $domain;
            $this->load($domain, $this->text_domains[$this->domain]['path']);
        }
        return $this->domain;
    }

    /**
     * Overrides the domain for a single lookup
     * This function allows you to override the current domain for a single message lookup.
     * It also allows you to specify a category.
     * Categories are folders within the languages directory  .
     * currently, only LC_MESSAGES is implemented
     *
     *   The values for categories are:
     *   LC_CTYPE        0
     *   LC_NUMERIC      1
     *   LC_TIME         2
     *   LC_COLLATE      3
     *   LC_MONETARY     4
     *   LC_MESSAGES     5
     *   LC_ALL          6
     *
     *   not yet implemented
     */
    public function dcgettext ($domain, $message, $category) {
        return $message;
    }

    /**
     * dcngettext -- Plural version of dcgettext
     * not yet implemented
     */
    public function dcngettext ($domain, $msg1, $msg2, $count, $category) {
        return $msg1;
    }


    /**
     * Plural-Forms: nplurals=2; plural=n == 1 ? 0 : 1;
     *
     * nplurals - total number of plurals
     * plural   - the plural index
     *
     * Plural-Forms: nplurals=1; plural=0;
     * 1 form only
     *
     * Plural-Forms: nplurals=2; plural=n == 1 ? 0 : 1;
     * Plural-Forms: nplurals=2; plural=n != 1;
     * 2 forms, singular used for one only
     *
     * Plural-Forms: nplurals=2; plural=n>1;
     * 2 forms, singular used for zero and one
     *
     * Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2;
     * 3 forms, special case for zero
     *
     * Plural-Forms: nplurals=3; plural=n==1 ? 0 : n==2 ? 1 : 2;
     * 3 forms, special cases for one and two
     *
     * Plural-Forms: nplurals=4; plural=n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3;
     * 4 forms, special case for one and all numbers ending in 02, 03, or 04
     */
    private function getplural ($count, $domain) {
        if (isset($this->headers[$domain]['Plural-Forms'])) {
            $plural_forms = $this->headers[$domain]['Plural-Forms'];
            preg_match('/nplurals[\s]*[=]{1}[\s]*([\d]+);[\s]*plural[\s]*[=]{1}[\s]*(.*);/', $plural_forms, $matches);
            $nplurals   = $matches[1];
            $plural_exp = $matches[2];
            if ($nplurals > 1 && strpos($plural_exp, ':') === false) {
                $plural =  'return ('.preg_replace('/n/', $count, $plural_exp).') ? 1 : 0;';
            } else {
                $plural = 'return '.preg_replace('/n/', $count, $plural_exp).';';
            }
        }
        else $plural = 'return '.preg_replace('/n/', $count, 'n != 1 ? 1 : 0;');
        return eval($plural);
    }

    private function load ($domain, $path) {
        $root = dirname(__FILE__);
        $catalog = new PHPGettextFileMO (true, $domain, $path, $this->lang);
        $this->headers[$domain] = $catalog->headers;
        foreach ($catalog->strings as $string) $this->messages[$domain][$string->msgid] = $string->msgstr;
    }
    //Thank you - Inicio Agregado Andres Felipe Vargas
    // Cannot find that this is used anywhere?
    function add ($domain) {
        $catalog = new PHPGettextFileMO (true, $domain, $this->text_domains[$this->domain]["path"], $this->lang);
        foreach ($catalog->strings as $string)
        $this->messages[$this->domain][$string->msgid] = $string->msgstr;
    }
    //end Inicio Agregado Andres Felipe Vargas

}