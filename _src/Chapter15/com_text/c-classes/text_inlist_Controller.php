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

class text_inlist_Controller extends textUserControllers {

	private static $instance = null;

	// The controller should be a singleton
	public static function getInstance ($manager) {
		if (null == self::$instance) self::$instance = new self($manager);
		return self::$instance;
	}

	public function inlist ($task, $id=0) {
		$displaytask = new text_display_Controller($this->manager);
		if (0 == $id) $id = $this->getParam($_REQUEST, 'id', 0);
		
		$viewer = new text_general_HTML();
		$tagstext = $this->getParam($_REQUEST, 'tags');
		$tags = explode(',', $tagstext);
		foreach ($tags as $sub=>$tag) {
			$tag = intval($tag);
			if ($tag) $tags[$sub] = $tag;
			else unset ($tags[$sub]);
		}
		if (empty($tags)) {
			$displaytask->display($task, $id);
			$viewer->showNextPrevious('',0,0);
		}
		else {
			$tagnames = aliroTagHandler::getInstance()->findTagNames($tags);
			$taglist = implode(',', $tags);
			$database = aliroDatabase::getInstance();
			$database->setQuery("SELECT t.id FROM #__simple_text AS t"
			."\nINNER JOIN #__simple_text_tags AS tt ON t.id = tt.text_id AND tt.describes != 0"
			."\nWHERE tt.tag_id IN ($taglist)"
			."\nAND t.published != 0 AND publish_start < NOW() "
			."\nAND (publish_end = '0000-00-00 00:00:00' OR publish_end > NOW())");
			$describes = $database->loadResultArray();
			$dlist = empty($describes) ? '' : implode(',', $describes);
			$displaytask->display($task, $id, $tagnames, $dlist);
		
			$texts = $database->doSQLget("SELECT t.id, t.headline, tt.tag_id FROM #__simple_text AS t"
			."\nINNER JOIN #__simple_text_tags AS tt ON t.id = tt.text_id AND tt.describes = 0"
			."\nWHERE tt.tag_id IN ($taglist)"
			."\nAND t.published != 0 AND publish_start < NOW() "
			."\nAND (publish_end = '0000-00-00 00:00:00' OR publish_end > NOW())");
			if (empty($texts)) $viewer->showNextPrevious($taglist,0,0);
			else {
				$id = $this->getParam($_REQUEST, 'id', 0);
				if (0 == $id) {
					$viewer->showNextPrevious($taglist,0,0);
					return;
				}
				$previous = 0;
				foreach ($texts as $text) {
					if ($id == $text->id) $next = 0;
					elseif (!isset($next)) $previous = $text->id;
					elseif (0 == $next) $next = $text->id;
					else break;
				}
				$viewer->showNextPrevious($taglist, $previous, $next);
			}
		}
	}
	
	private function findDescribers ($taglist) {
		$database = aliroDatabase::getInstance();
		$database->setQuery("SELECT t.id FROM #__simple_text AS t"
		."\nINNER JOIN #__simple_text_tags AS tt ON t.id = tt.text_id AND tt.describes != 0"
		."\nWHERE tt.tag_id IN ($taglist)"
		."\nAND t.published != 0 AND publish_start < NOW() "
		."\nAND (publish_end = '0000-00-00 00:00:00' OR publish_end > NOW())");
		// Not currently implemented
	}

}