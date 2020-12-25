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
 * aliroRenderer is part of a simple templating framework.  In Aliro, it is used
 * only in the area of language processing.
 *
 * aliroPHPRenderer is a template renderer that works with pure PHP templates
 *
 */

class aliroRenderer {
	
	public static function getRenderer ($type='php') {
		if ('php' == $type) return new aliroPHPRenderer();
		else {
			$classname = $type.'Renderer';
			if (aliro::getInstance()->classExists($classname)) return new $classname();
        }
        trigger_error(T_('aliroRenderer called for invalid renderer type'), E_USER_ERROR);
	}
}

class aliroPHPRenderer extends basicAdminHTML implements ifTemplateRenderer  {
    private $dir;
    private $vars = array();
    protected $engine = 'php';
    protected $template = '';
    private $debug = 0;
	protected $translations = array();
	public $act = '';
	public $pageNav = null;

    public function __construct () {
    	$this->dir = _ALIRO_CLASS_BASE.'/views/templates/';
    }

    public function display ($template='') {
        return $this->checkTemplate($template) ? $this->loadTemplate($this->template) : false;
    }

    public function fetch ($template='') {
        if ($this->checkTemplate($template)) {
            ob_start();
			$this->loadTemplate($this->template);
            $ret = ob_get_contents();
            ob_end_clean();
            return $ret;
        }
        return false;
    }
    
    private function loadTemplate ($template) {
    	extract($this->vars);
		if (!empty($act)) $this->act = $act;
    	include($this->template);
    	return true;
    }
    
    private function checkTemplate ($template) {
    	if (empty($template)) $template = $this->template;
        if ($this->debug) echo nl2br($this->template."\n");
        if (empty($template)) trigger_error(T_('A template has not been specified in a call to aliroRenderer'), E_USER_ERROR);
        elseif (!is_readable($this->dir.$template)) trigger_error(sprintf(T_('Specified template file %s is not readable in a call to aliroPHPRenderer'), $template), E_USER_ERROR);
    	else {
    		$this->template = $this->dir.$template;
    		return true;
    	}
    	return false;
    }

    public function getengine(){
        return $this->engine;
    }

    public function addvar($key, $value){
        $this->vars[$key] = $value;
    }

    public function addbyref ($key, &$value) {
        $this->vars[$key] = $value;
    }

    public function getvars ($name) {
        return isset($this->vars[$name]) ? $this->vars[$name] : '';
    }

    public function setdir ($dir) {
        $this->dir = (substr($dir, -1) == '/') ? $dir : $dir.'/';
    }

    public function settemplate ($template){
        $this->template = $template;
    }

    // Provides for aliroHTML methods to be used within heredoc as $this->html('method', ...)
	protected function html () {
		$args = func_get_args();
		$method = array_shift($args);
		$html = aliroHTML::getInstance();
		return call_user_func_array(array($html, $method), $args);
	}
   
}