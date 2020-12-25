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
 * aliroScreenArea is the abstract class used to define a browser area - the locations
 * into which module output is placed.  It also provides a static method for building
 * the output for all screen areas for a template.  The output is captured at this point,
 * so that module code is able to create new information in the HTML header etc.
 *
 * aliroUserScreenArea and aliroAdminScreenArea are the classes that are actually
 * instantiated on the user and admin sides respectively.  Further work is needed
 * clarify the operation of admin side modules.
 *
 */

abstract class aliroScreenArea {
	public $name = '';
	public $min_width = 0;
	public $max_width = 0;
	public $style = 0;
	protected $screen_data = '';

	public function __construct ($name='', $min_width='', $max_width='', $style='') {
		$this->name = $name;
		$this->min_width = $min_width;
		$this->max_width = $max_width;
		$this->style = $style;
	}

	public static function prepareTemplate ($template) {
		$areas = $template->positions();
		foreach ($areas as $area) {
			ob_start();
			$area->loadModules($template);
			$area->setData(ob_get_contents());
			ob_end_clean();
		}
	}
	
	public function setData ($data) {
		$this->screen_data = $data;
	}

	public function addData ($data) {
		$this->screen_data .= $data;
	}

	public function getData () {
		return $this->screen_data;
	}

}

class aliroUserScreenArea extends aliroScreenArea {

	public function countModules () {
		return aliroModuleHandler::getInstance()->countModules($this->name, false);
	}

	public function loadModules ($template) {
		$modules = aliroModuleHandler::getInstance()->getModules($this->name, false);
		$count = count($modules);
		foreach ($modules as $i=>$module) {
			// Could add output directly into module object, but this method captures any diagnostic etc output
			echo $module->renderModule($this, $template, $i, $count);
		}
	}

}

class aliroAdminScreenArea extends aliroScreenArea {

	public function countModules () {
		return aliroModuleHandler::getInstance()->countModules($this->name, true);
	}

	public function loadModules ($template) {
		$modules = aliroModuleHandler::getInstance()->getModules($this->name, true);
		$count = count($modules);
		$authoriser = aliroAuthoriser::getInstance();
		foreach ($modules as $i=>$module) {
			if ($authoriser->checkUserPermission ('view', 'aliroModule', $module->id)) {
				// $moduleid was second parameter, but not being supplied???
				// if ($moduleid AND $moduleid != $module->id) echo $module->renderModuleTitle($this, $template);
				echo $module->renderModule($this, $template, $i);
			}
		}
	}

}
