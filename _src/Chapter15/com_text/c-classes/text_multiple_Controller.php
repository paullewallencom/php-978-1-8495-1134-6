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

class text_multiple_Controller extends textUserControllers {

	private static $instance = null;

	// The controller should be a singleton
	public static function getInstance ($manager) {
		if (null == self::$instance) self::$instance = new self($manager);
		return self::$instance;
	}
	
	public function multiple ($task) {
		$ids = $this->getParam($_REQUEST, 'ids');
		if ($ids) {
			$displaytask = text_display_Controller::getInstance();
			$idarray = explode(',', $ids);
			foreach ($idarray as $id) {
				$id = intval($id);
				if ($id) $displaytask->display($this->manager, $id);
			}
		}
	}

}
