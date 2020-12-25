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
 * aliroAbstractRequest is the abstract central class for handling every request.
 * It is extended by aliroAdminRequest and aliroUserRequest to implement the 
 * specific requirements of the admin and user sides respectively.
 *
 */

abstract class aliroAbstractRequest {
	// Singleton object holder - will contain the single instance of aliroUserRequest or aliroAdminRequest
	protected static $instance = null;
	protected static $objectInstances = array();

	// Request attributes
	protected $option = '';
	protected $task = '';
	protected $isHome = false;
	protected $formcheck = 0;
	protected $component_name = '';
	protected $component = null;
	protected $bestmatch = null;
	protected $urilinkid = 0;
	protected $aliroVersion = '';
	protected $urlerror = false;
	protected $title = '';
	protected $metatags = array();
	protected $customtags = array();
	protected $endbodytags = array('early' => array(), 'default' => array(), 'late' => array());
	protected $templateName = '';
	protected $templateObject = null;
	// protected $do_gzip = false;
	protected $error_message = array();
	protected $overlib = false;
	protected $noredirectok = true;
	protected $component_diagnostics = '';

	// Core singleton objects providing key information resources
	protected $critical = null;
	protected $configuration = null;
	protected $pathway = null;
	protected $version = null;
	protected $uniques = array();

	// Singleton "handler" objects
	protected $mhandler = null;
	protected $chandler = null;
	protected $xhandler = null;
	protected $purifier = null;

// The initial functions here are internal to Aliro, most are private or protected

	protected function __construct () {
        @set_magic_quotes_runtime( 0 );
        //require_once(_ALIRO_CLASS_BASE.'/includes/phpgettext/phpgettext.class.php');
		// Note that none of the things called here can use aliroAbstractRequest!
		// Otherwise, a loop will be created and Aliro will fail!
		// Ensure session started straight away

		$this->uriHackCheck();
		$this->getSession();
		// Check for problems with globals - do after session has started to be able to handle session variables
		$this->handleGlobals();
		$this->setUsefulObjects();
		// gzip abandoned - use Apache mod_deflate
        // if (extension_loaded('zlib') AND $this->configuration->getCfg('gzip')) $this->do_gzip = true;
		$this->setHandlers();
		if (isset($_SESSION['_aliro_orphan_data'])) {
			if (0 == count(array_diff($_GET, $_SESSION['_aliro_orphan_data']['get']))) {
	   			foreach (array_keys($_POST) as $key) unset($_REQUEST[$key]);
	   			$_POST = $_SESSION['_aliro_orphan_data']['post'];
	   			foreach ($_POST as $key=>$value) $_REQUEST[$key] = $value;
			}
   			unset($_SESSION['_aliro_orphan_data']);
		}
		if (count($_POST)) $this->fixPostItems();
		$this->option = $this->component_name = strtolower($this->getParam($_REQUEST, 'option'));
		if (!empty($_SESSION['aliro_component_diagnostics'])) {
			$this->component_diagnostics = $_SESSION['aliro_component_diagnostics'];
			unset($_SESSION['aliro_component_diagnostics']);
		}
	}
	
	private function uriHackCheck () {
		$uri = isset($_SERVER['REQUEST_URI']) ? urldecode($_SERVER['REQUEST_URI']) : '';
		if (preg_match(_ALIRO_REGEXP_URL, $uri) OR preg_match(_ALIRO_REGEXP_IP, $uri)) {
			new aliroPage403(T_('URI appears to contain a link'));
			exit;
		}
	}

	private function setHandlers () {
        $this->mhandler = aliroMenuHandler::getInstance();
        $this->chandler = aliroComponentHandler::getInstance();
        $this->xhandler = aliroExtensionHandler::getInstance();
	}

	private function setUsefulObjects () {
		$this->critical = criticalInfo::getInstance();
    	$this->version = version::getInstance();
   		$this->aliroVersion = $this->version->RELEASE.'/'.$this->version->DEV_STATUS.'/'.$this->version->DEV_LEVEL;
		$this->configuration = aliroCore::getInstance();
		$this->configuration->fixLanguage();
	}

	protected function fixPostItems () {
		$this->formcheck = $this->checkFormStamp();
		if (_ALIRO_FORM_CHECK_EXPIRED == $this->formcheck OR _ALIRO_FORM_CHECK_FAIL == $this->formcheck) {
			$this->setErrorMessage(T_('Sorry, your request used an invalid or expired form, please try again'));
			$_POST = array();
		}
		if (_ALIRO_FORM_CHECK_REPEAT == $this->formcheck) {
			$this->setErrorMessage(T_('This form submission has already been processed'));
			$_POST = array();
		}
		$params = $this->getParam($_POST, 'params', null, _MOS_ALLOWHTML);
		if ($params) {
			$pobject = new aliroParameters();
			$pobject->processInput($params);
			$_POST['params'] = $pobject->asString();
		}
		if (isset($_POST['alironstask']) AND (!isset($_REQUEST['task']) OR !$_REQUEST['task'])) $_POST['task'] = $_REQUEST['task'] = $_POST['alironstask'];
	}

	protected function __clone () {
		// Declared to enforce singleton
	}

	public function __call ($method, $args) {
		// May want to add language
		foreach (array($this->configuration, $this->pathway) as $object) {
			if (method_exists($object, $method)) return call_user_func_array(array($object, $method), $args);
		}
		if (aliro::getInstance()->installed) {
			trigger_error (sprintf(T_('Invalid method call on aliroRequest - %s'), $method));
			echo aliroRequest::trace();
		}
		return null;
	}

	public function __get ($property) {
		if (isset($this->critical->$property)) return $this->critical->$property;
		trigger_error (sprintf(T_('Invalid property request on aliroAbstractRequest - %s'), $property));
		return null;
	}

    private function handleGlobals () {
        $superglobals = array($_SERVER, $_ENV, $_FILES, $_COOKIE, $_POST, $_GET);
		if (isset($_SESSION)) array_push ($superglobals, $_SESSION);
        // Emulate register_globals off
        if (ini_get('register_globals')) {
            foreach ($superglobals as $superglobal) {
                foreach ($superglobal as $key=>$value) {
                    unset( $GLOBALS[$key]);
                }
            }
        }
    }
    
    // The methods from here are generally available up to the comment that says otherwise
    
    public function getSession () {
    	return aliroSession::getSession();
	}
	
	public function getIP () {
		return aliroSession::getSession()->getIP();
	}
	
	public function getSpamOptions () {
		return aliroSpamHandler::getInstance()->getSpamOptions();
	}
    
    public function getClassObject ($classname) {
		if (!isset(self::$objectInstances[$classname]) AND aliro::getInstance()->classExists($classname)) {
			// Pass null parameter to help standard component classes
    		self::$objectInstances[$classname] =  method_exists($classname, 'getInstance') ? call_user_func(array($classname,'getInstance'), null) : new $classname(null);
		}
		return isset(self::$objectInstances[$classname]) ? self::$objectInstances[$classname] : null;
	}

	public function doClassMethod ($classname, $method, $args) {
		if (method_exists($classname, $method)) return call_user_func_array(array($classname,$method), $args);
		else {
			$object = $this->getClassObject($classname);
			if (is_object($object) AND method_exists($object, $method)) return call_user_func_array(array($object, $method, $args));
		}
		trigger_error(T_('Request to doClassMethod failed'));
	}
	
	public function noRedirectHere () {
	    if ($this->noredirectok AND !empty($_SESSION['aliro_redirect_here'])) {
			array_shift($_SESSION['aliro_redirect_here']);
			$this->noredirectok = false;
		}
	}
	
	public function goBack ($message='', $severity='') {
	    $uri = !empty($_SESSION['aliro_redirect_here']) ? array_shift($_SESSION['aliro_redirect_here']) : '';
		$this->redirect($uri, $message, $severity);		
	}

    public function redirect ($url='', $message='', $severity=_ALIRO_ERROR_INFORM) {
    	if (empty($url)) $url = '';
    	else {
    		$url = $this->stripFromURL($url, 'mosmsg');
    		$url = $this->stripFromURL($url, 'severity');
    	}
		if (strpos($url, 'http') !== 0) {
			if ($url AND $url[0] != '/') $url = '/'.$url;
			$url = $this->siteBaseURL.$url;
		}
        if ($message) {
        	$_SESSION['aliro_user_message'] = array('message' => $message, 'severity' => $severity);
		}
		if ($this->getCfg('debug')) {
			$chandler = aliroComponentHandler::getInstance();
			$chandler->endBuffer();
			if (!isset($_SESSION['aliro_component_diagnostics'])) $_SESSION['aliro_component_diagnostics'] = '';
			$_SESSION['aliro_component_diagnostics'] .= $this->component_diagnostics.'<br />Redirecting to '.$url.'<br />'.$chandler->getBuffer();
		}
        session_write_close();
        if (headers_sent()) printf (T_('Please click on %s this link %s to continue'), "<a href='$url'>", '</a>');
        else {
            @ob_end_clean(); // clear output buffer
            header( "Location: $url" );
        }
        exit();
    }

    public function redirectSame ($message='', $severity=_ALIRO_ERROR_INFORM) {
		$query = $_SERVER['QUERY_STRING'];
		if ('option=login' == $query) $query = 'option=com_login';
    	$url = 'index.php?'.$query;
    	$this->redirect ($url, $message, $severity);
    }

	public function getUnique () {
		do {
			$random = (string) mt_rand(100000,999999); 
		}
		while (in_array($random, $this->uniques));
		$this->uniques[] = $random;
		return $random;
	}

	public function getComponentName () {
		return $this->component_name;
	}

    public function getItemid () {
    	return $this->urilinkid ? 100000+$this->urilinkid : (isset($this->bestmatch) ? $this->bestmatch->id : 0);
    }

    public function getOption () {
    	return $this->option;
    }
    
    public function setTask ($task) {
    	$this->task = $task;
	}
	
	public function getTask () {
		return $this->task;
	}
    
    public function getMenu () {
    	return $this->bestmatch;
	}

    public function stripFromURL ($url, $property) {
		$position = strpos($url, $property);
    	if ($position) {
			$endpos = strpos($url, '&', $position);
    		if ($endpos) $url = substr($url, 0, $position).substr($url, $endpos+1);
    		else $url = substr($url, 0, $position-1);
    	}
		return $url;
    }

    public function setErrorMessage ($message, $severity=_ALIRO_ERROR_FATAL) {
    	$this->error_message[$severity][] = $message;
    }
    
    public function insertErrorMessage ($message, $severity=_ALIRO_ERROR_FATAL) {
    	if (empty($this->error_message[$severity])) $this->setErrorMessage($message, $severity);
    	else array_unshift($this->error_message[$severity], $message);
	}
	
	public function insertMessageFromSession () {
		$message = $this->getParam($_SESSION, 'aliro_user_message');
		if ($message) {
			$this->insertErrorMessage($message['message'], intval($message['severity']));
			unset($_SESSION['aliro_user_message']);
		}
	}

    public function isErrorLevelSet ($severity) {
    	return isset($this->error_message[$severity]);
    }

    public function pullErrorMessages () {
    	$messages = $this->error_message;
    	$this->error_message = array();
    	return $messages;
    }
	
	public function pullLastMostSevereMessage () {
		$messages = $this->pullErrorMessages();
		foreach (array(_ALIRO_ERROR_FATAL, _ALIRO_ERROR_SEVERE, _ALIRO_ERROR_WARN, _ALIRO_ERROR_INFORM) as $severity) if (isset($messages[$severity])) {
			$count = count($messages[$severity]);
			return $messages[$severity][$count-1];
		}
		return '';
	}

    public function getUserState( $var_name ) {
        return is_array($_SESSION["aliro_{$this->prefix}state"]) ? $this->getParam($_SESSION["aliro_{$this->prefix}state"], $var_name) : null;
    }

	public function setUserState( $var_name, $var_value ) {
        $_SESSION["aliro_{$this->prefix}state"][$var_name] = $var_value;
    }

    public function getUserStateFromRequest($var_name, $req_name, $var_default=null) {
        if (isset($_REQUEST[$req_name])) {
        	if ((string) $var_default == (string) (int) $var_default) $_REQUEST[$req_name] = intval($_REQUEST[$req_name]);
        	$this->setUserState($var_name, $_REQUEST[$req_name]);
        }
        elseif (isset($var_default) AND !$this->isUserStateSet($var_name)) $this->setUserState($var_name, $var_default);
        return $this->getUserState($var_name);
    }

    public function makeFormStamp () {
    	$formid = md5(uniqid(mt_rand(), true));
		$checker = md5(uniqid(mt_rand(), true));
		$_SESSION['aliro_formid_'.$formid] = $checker;
		$_SESSION['aliro_formdone_'.$formid] = 0;
		$html = <<<FORM_STAMP
		<input type="hidden" name="aliroformid" value="$formid" />
		<input type="hidden" name="alirochecker" value="$checker" />
FORM_STAMP;
		return $html;
    }

    public function getFormCheckError () {
    	$messages = array (
    	_ALIRO_FORM_CHECK_EXPIRED => T_('Sorry, the form you used has expired, please try again'),
    	_ALIRO_FORM_CHECK_FAIL => T_('Sorry, the form you used is invalid'),
    	_ALIRO_FORM_CHECK_NULL => T_('Sorry, the form you used did not have a required authentication'),
    	_ALIRO_FORM_CHECK_REPEAT => T_('The form you used has already been processed')
    	);
    	if ($this->formcheck) {
	    	if (isset($messages[$this->formcheck])) return $messages[$this->formcheck];
	    	else return T_('Internal error - invalid form check value');
    	}
    	else return '';
    }

	public function getParam ($arr, $name, $def=null, $mask=0) {
	    if (isset( $arr[$name] )) {
	        if (is_array($arr[$name])) foreach ($arr[$name] as $key=>$element) {
	        	$result[$key] = $this->getParam ($arr[$name], $key, $def, $mask);
	        }
	        else {
	            $result = $arr[$name];
	            if (!($mask&_MOS_NOTRIM)) $result = trim($result);
	            if (!is_numeric($result)) {
	            	if (get_magic_quotes_gpc() AND !($mask & _MOS_NOSTRIP)) $result = stripslashes($result);
	                if (!($mask&_MOS_ALLOWRAW) AND is_numeric($def)) $result = $def;
	                elseif ($result) {
	                	if ($mask & _MOS_ALLOWHTML) $result = $this->doPurify($result);
		                else {
							$result = strip_tags($result);
							// $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
						}
	                }
	            }
	        }
	        return $result;
	    }
	    return $def;
	}

	public function doPurify ($string) {
		if (null == $this->purifier) {
	  		$config = HTMLPurifier_Config::createDefault();
	   		if (_ALIRO_IS_ADMIN) {
				$config->set('HTML', 'Trusted', true);
				$config->set('Attr', 'EnableID', true);
			}
	  		$this->purifier = new HTMLPurifier($config);
		}
		return $this->purifier->purify($string);
	}

	// Cannot be applied to items that return an array, only to a scalar
	public function getStickyParam ($arr, $name, $def=null, $mask=0) {
		$var = 'aliro_sticky_'.$this->getComponentName().'_'.$name;
		return $this->getSticky ($var, $arr, $name, $def=null, $mask=0);
	}

	public function getStickyAliroParam ($arr, $name, $def=null, $mask=0) {
		$var = 'aliro_sticky_aliro_'.$name;
		return $this->getSticky ($var, $arr, $name, $def=null, $mask=0);
	}

	private function getSticky ($var, $arr, $name, $def, $mask) {
		if ((!isset($arr[$name]) OR !$arr[$name]) AND isset($_SESSION[$var])) return $_SESSION[$var];
		$provided = $this->getParam($arr, $name, $def, $mask);
		if ($provided) $_SESSION[$var] = $provided;
		return $provided;
	}

	public function unstick ($name) {
		$var = 'aliro_sticky_'.$this->getComponentName().'_'.$name;
		if (isset($_SESSION[$var]))	unset ($_SESSION[$var]);
	}

	public function getTemplate () {
		if (!$this->templateName) $this->templateName = aliroTemplateHandler::getInstance()->getDefaultTemplateName();
		return $this->templateName;
    }

    public function setPageTitle ($title=null) {
    	$config = aliroComponentConfiguration::getInstance('cor_sef');
        if (!empty($config->pagetitles)) {
            $title = trim($title);
            $base = $this->getCfg('sitename');
            $this->title = $title ?  $title.' - '.$base : $base;
        }
    }

    public function getPageTitle () {
        return $this->title;
    }

	public function addMetaTag($name, $content, $prepend='', $append='') {
		$this->fix_metatag ('new', $name, $content, $prepend, $append);
	}

    public function appendMetaTag ($name, $content) {
    	$this->fix_metatag ('post', $name, $content);
    }

    public function prependMetaTag ($name, $content) {
    	$this->fix_metatag ('pre', $name, $content);
    }

    public function addCustomHeadTag ($html, $position='head') {
        if (isset($this->endbodytags[$position])) $this->endbodytags[$position][] = trim ($html);
        else $this->customtags[] = trim ($html);
    }
    
    //DEPRECATED: See addScriptNode
	public function addScript ($relativeFile, $position='default', $notRelative=false) {
		$site = $notRelative ? '' : $this->getCfg('live_site');
		$link = <<<SCRIPT_LINK

	<script type="text/javascript" src="$site$relativeFile"></script>

SCRIPT_LINK;

		$this->addCustomHeadTag($link, $position);
	}
    
    //DEPRECATED: See addScriptEmbed
	public function addScriptText ($text, $position='default') {
		$link = <<<SCRIPT_LINK

	<script type="text/javascript">
		$text
	</script>

SCRIPT_LINK;

		$this->addCustomHeadTag($link, $position);
	}
	
	//Expects fully qualified inline script nodes (e.g.) <script ...>foo</script>
    public function addScriptEmbed ($rawScript, $position='default', $minify=false) {
		if ($minify===true) {
		    require_once aliroCore::getInstance()->getCfg('absolute_path')."/includes/minify/min/lib/JSMin.php";
		    
		    $rawScript = preg_replace_callback(
                        '@<script[^>]*?>.*?</script>@si',
			            create_function(
                            '$matches',
                            'return JSMin::minify($matches[0]);'
                        ),
                        $rawScript
                    );
		}

		$this->addCustomHeadTag($rawScript, $position);
	}

	//Expects a raw block of prebuild script nodes (Note: use case PHP Loader)
	public function addScriptNodes ($scriptNodes, $position='default') {
		$this->addCustomHeadTag($scriptNodes, $position);
	}
    
    //DEPRECATED: See addLinkNodes
	public function addCSS ($relativeFile, $media='screen') {
		$link = <<<CSS_LINK

	<link href="{$this->getCfg('live_site')}$relativeFile" rel="stylesheet" type="text/css" media="$media" />

CSS_LINK;

		$this->addCustomHeadTag($link);
	}

	//Expects fully qualified inline style nodes (e.g.) <style ...>foo</style>
	public function addCSSEmbed ($rawCSS, $position='head', $minify=false) {
		if ($minify===true) {
		    require_once aliroCore::getInstance()->getCfg('absolute_path')."/includes/minify/min/lib/Minify/CSS/Compressor.php";
		    
		    $rawCSS = preg_replace_callback(
                        '@<style[^>]*?>.*?</style>@si',
			            create_function(
                            '$matches',
                            'return Minify_CSS_Compressor::process($matches[0]);'
                        ),
                        $rawCSS
                    );
		}

		$this->addCustomHeadTag($rawCSS, $position);
	}
	
	//Expects a raw block of prebuild link nodes (Note: use case PHP Loader)
	public function addLinkNodes ($linkNodes) {
		$this->addCustomHeadTag($linkNodes);
	}

    public function requestOverlib () {
    	if ($this->overlib) return;
    	$this->addScript('/includes/js/overlib_mini.js');
		$this->overlib = true;
    }

    public function divOverlib () {
    	if ($this->overlib) return '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:10000;"></div>';
    	return '';
    }

    // All methods from here on are Aliro internal, some are protected or private
    
	// Internal to Aliro
    public function showHead () {
        $html = "<base href=\"{$this->getCfg('live_site')}/\" />\r\n";
        $html .= aliroSEF::getInstance()->getHead($this->title, $this->metatags, $this->customtags);
        if (aliroUser::getInstance()->id ) $html .= "<script src='{$this->getCfg('live_site')}/includes/js/alirojavascript.js' type='text/javascript'></script>";
		return $html;
    }

    protected function isUserStateSet ($var_name) {
    	return isset($_SESSION["aliro_{$this->prefix}state"][$var_name]);
    }

    private function checkFormStamp () {
    	$formid = $this->getParam($_POST, 'aliroformid');
    	$checker = $this->getParam($_POST, 'alirochecker');
		$userid = aliroUser::getInstance()->id;
    	if ($formid) {
    		if (!isset($_SESSION['aliro_formid_'.$formid])) {
				return $userid ? _ALIRO_FORM_CHECK_OK : _ALIRO_FORM_CHECK_EXPIRED;
			}
    		if ($_SESSION['aliro_formid_'.$formid] == $checker) {
    			if ($_SESSION['aliro_formdone_'.$formid]) return _ALIRO_FORM_CHECK_REPEAT;
    			else {
    				$_SESSION['aliro_formdone_'.$formid] = 1;
    				return _ALIRO_FORM_CHECK_OK;
    			}
    		}
    		else {
    			$this->setErrorMessage(T_('Form failed consistency check'), _ALIRO_ERROR_FATAL);
    			return _ALIRO_FORM_CHECK_FAIL;
    		}
    	}
    	else return _ALIRO_FORM_CHECK_NULL;
    }

    protected function fix_metatag ($operation, $name, $content, $prepend='', $append='') {
    	$content = trim(htmlspecialchars($content));
		if (!$content) return;
    	$name = trim(htmlspecialchars($name));
        $prepend = trim($prepend);
        $append = trim($append);
    	if ('new' == $operation) $this->metatags[$name] = array($content, $prepend, $append);
    	else {
    		$tag = isset($this->metatags[$name]) ?  $this->metatags[$name] : array('', '', '');
    		if ('pre' == $operation) $tag[0] = $content.$tag[0];
			else $tag[0] = $content.(($tag[0] AND $content) ? ',' : '').$tag[0];
			$this->metatags[$name] = $tag;
    	}
    }

	// Internal to Aliro
    public function setMetadataInCache (&$cache_object) {
    	$cache_object->title = $this->title;
    	$cache_object->metatags = $this->metatags;
    	$cache_object->customtags = $this->customtags;
    }

	// Internal to Aliro
    public function setMetadataFromCache ($cache_object) {
    	$this->title = $cache_object->title;
    	$this->metatags = $cache_object->metatags;
    	$this->customtags = $cache_object->customtags;
    }

    // Internal to Aliro
    public function getDebug () {
    	if ($this->getCfg('debug')) {
    		//aliroSEF::getInstance()->saveCache();
			$diagnostics = $this->component_diagnostics;
			if ($diagnostics) {
				$heading = T_('Diagnostic output brought forward from redirection:');
				$log = <<<DIAGNOSTICS
				
				<h3>$heading</h3>
				<p>
					$diagnostics
				</p>
				
DIAGNOSTICS;

			}
			else $log = '';
			$database = aliroDatabase::getInstance();
			$log .= $database->getLogged();
			$database = aliroCoreDatabase::getInstance();
			$log .= $database->getLogged();
			$loader = aliroDebug::getInstance();
			$log .= $loader->getLogged();
			return $log;
    	}
    	else return '';
    }

    // Internal to Aliro
    public function getCustomTags () {
        if (count($this->customtags)) return implode("\n", $this->customtags);
        return '';
	}
	
	// Internal to Aliro
	public function getEndBodyTags () {
		foreach (array('early', 'default', 'late') as $position) {
			$html[] = implode("\n\t\t", $this->endbodytags[$position]);
    	}
    	$sefconfig = aliroComponentConfiguration::getInstance('cor_sef');
    	if (!empty($sefconfig->google_analytics)) $html[] = <<<GOOGLE_ANALYTICS
    	
<script type="text/javascript"><!--//--><![CDATA[//><!--
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
//--><!]]></script>
<script type="text/javascript"><!--//--><![CDATA[//><!--
var pageTracker = _gat._getTracker("$sefconfig->google_analytics");
pageTracker._initData();
pageTracker._trackPageview();
//--><!]]></script>
    	
GOOGLE_ANALYTICS;
  
    	return isset($html) ? "\n\t\t".implode("\n\t\t", $html) : '';
	}

	// Internal to Aliro
    public function getComponentObject ($component=null) {
    	if (is_object($component)) $this->component = $component;
    	if (is_object($this->component)) return $this->component;
    	if ($this->core_item) {
			$this->component = new aliroComponent();
			$this->component->option = $this->component->extformalname = $this->core_item;
			$this->component->name = $this->core_item;
			$this->component->adminclass = 'aliroComponentAdminManager';
    	}
    	else $this->component = $this->chandler->getComponentByFormalName($this->option);
    	return $this->component;
    }

    protected function invokeComponent ($menu=null) {
    	try {
			$this->chandler->startBuffer();
			if (!$this->option AND $menu AND $menu->component) $this->option = $menu->component;
			$component = $this->getComponentObject();
			$message = T_('At entry of aliroRequest::invokeComponent');
			if (!$this->urlerror AND ($this->option OR $this->core_item)) {
				$componentname = $this->option? $this->option : $this->core_item;
				define ('_ALIRO_COMPONENT_NAME', $componentname);
				if ($component) {
					if ($this->pathway) {
						$cname = aliroSEF::getInstance()->sefComponentName($component->option);
						$this->pathway->addItem($cname, 'index.php?option='.$component->option);
					}
					$class = $this->getComponentClass($component);
					if ($class) $this->standardCall ($component, $class, $menu);
					else $this->urlerror = $this->retroCall ($menu);
					if ($this->urlerror) trigger_error(T_('Retro call was unable to find component: ').$this->option);
				}
				else {
					$this->urlerror = true;
					$message = T_('Unable to find component object for ').$this->option;
				}
			}
			else {
				$this->urlerror = true;
				if ($this->chandler->componentCount() AND $this->mhandler->getMenuCount()) {
    				$message = sprintf(T_('Failed on urlerror from SEF or no option (%s)'), $this->option);
				}
			}
			if ($this->urlerror) new aliroPage404($message);
			$this->chandler->endBuffer();
    	} catch (databaseException $exception) {
    		$target = $this->core_item ? $this->core_item : $this->option;
    		$message = sprintf(T_('A database error occurred on %s at %s while processing %s'), date('Y-M-d'), date('H:i:s'), $target);
    		$errorkey = "SQL/{$exception->getCode()}/$target/$exception->dbname/{$exception->getMessage()}/$exception->sql";
    		aliroErrorRecorder::getInstance()->recordError($message, $errorkey, $message, $exception);
    		$this->redirect('', $message, _ALIRO_ERROR_FATAL);
    	}
    }

    protected function standardCall ($component, $class, $menu) {
		$worker = new $class ($component, 'Aliro', $this->aliroVersion, $menu);
		$worker->activate();
    }

    protected function retroCall ($menu) {
		if (!defined('JPATH_COMPONENT_SITE')) define ('JPATH_COMPONENT_SITE', _ALIRO_ABSOLUTE_PATH.'/administrator/components/'.$this->option);
		$mainframe = mosMainFrame::getInstance();
		$path = $mainframe->getPath($this->path_side);
		if (!$path) return true;
       	$this->invokeRetroCode($path, null, $menu);
       	return false;
    }

    // Internal to Aliro
    public function invokeRetroCode ($path, $function=null, $menu=null) {
       	$GLOBALS['task'] = $task = $this->getParam($_REQUEST, 'task');
       	$GLOBALS['act'] = $act = $this->getParam($_REQUEST, 'act');
   		$GLOBALS['id'] = $id = $this->getParam($_REQUEST, 'id', 0);
   		$GLOBALS['section'] = $section = $this->getParam($_REQUEST, 'section');
		require_once ($this->critical->absolute_path.'/includes/mambofunc.php');
       	$GLOBALS['acl'] = $acl = aliroAuthoriser::getInstance();
       	$GLOBALS['my'] = $my = aliroUser::getInstance();
		$GLOBALS['gid'] = $gid = $my->gid;
       	$GLOBALS['mainframe'] = $mainframe = mosMainFrame::getInstance();
       	$GLOBALS['database'] = $database = aliroDatabase::getInstance();
       	$GLOBALS['Itemid'] = $Itemid = $this->getItemid();
       	$GLOBALS['option'] = $option = $this->option;
       	$GLOBALS['_VERSION'] = $this->version;

       	// This will not do - what should happen??
       	$GLOBALS['mosConfig_lang'] = 'english';

       	error_reporting(E_ALL);
       	$this->globalizeConfig();
       	foreach ($GLOBALS as $key=>$value) if ('mosConfig_' == substr($key,0,10)) $$key = $value;
       	require($path);
       	if ($function) $function();
       	error_reporting(E_ALL|E_STRICT);
    }

}