<?php
/**
* Aliro module
* Copyright 2007 Martin Brampton
*/

class simpleManager extends aliroComponentUserManager {

	public function __construct () {
		$component = aliroComponentHandler::getInstance()->getComponentByFormalName('com_text');
		parent::__construct($component, 'task', array(), 'display', '', '', '', null);
	}

}

class mod_text implements ifAliroModule {

	function activate ($module, &$content, $area, $params) {
		$id = $params->get('id');
		if ($id) {
			$manager = new simpleManager();
			$controller = text_display_Controller::getInstance($manager);
			$content = $controller->displayForModule('display', $id);
		}
	}

}

class mod_text_list implements ifAliroModule {

	function activate ($module, &$content, $area, $params) {
		$taglist = $params->get('tags');
		$tagids = aliroTagHandler::getInstance()->namesToIds($taglist);
		if ($tagids) {
			$manager = new simpleManager();
			$controller = text_taglist_Controller::getInstance($manager);
			ob_start();
			ob_implicit_flush(false);
			$controller->taglist('taglist', $tagids);
			$content = ob_get_contents();
			ob_end_clean();
		}
	}

}