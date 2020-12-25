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

class text_taglist_Controller extends textUserControllers {

	private static $instance = null;

	// The controller should be a singleton
	public static function getInstance ($manager) {
		if (null == self::$instance) self::$instance = new self($manager);
		return self::$instance;
	}
	
	function taglist ($task, $tagstext='') {
		if (!$tagstext) $tagstext = $this->getParam($_REQUEST, 'tags');
		$tags = explode(',', $tagstext);
		foreach ($tags as $sub=>$tag) {
			$tag = intval($tag);
			if ($tag) $tags[$sub] = $tag;
			else unset ($tags[$sub]);
		}
		if (empty($tags)) echo T_('No valid tags specified');
		else {
			$taglist = implode(',', $tags);
			$database = aliroDatabase::getInstance();
			$texts = $database->doSQLget("SELECT t.*, tt.tag_id FROM #__simple_text AS t"
			."\nINNER JOIN #__simple_text_tags AS tt ON t.id = tt.text_id AND describes = 0"
			."\nWHERE tt.tag_id IN ($taglist)"
			."\nAND t.published != 0 AND publish_start < NOW() "
			."\nAND (publish_end = '0000-00-00 00:00:00' OR publish_end > NOW())", 'textItem');
			$database->setQuery("SELECT name FROM #__tags WHERE id IN ($taglist)");
			$tagnames = array_map('strtolower', $database->loadResultArray());
			$taglist = implode(' ', $tagnames);
			if (empty($texts)) $viewer->showNextPrevious($taglist,0,0);
			if (empty($texts)) echo T_('Sorry, no texts match specified tags');
			else {
				$event = ('yes' == $this->getParam($_REQUEST, 'blog')) ? 'onIntroText' : 'onMainText';
				foreach (array_keys($texts) as $sub) aliroMambotHandler::getInstance()->trigger($event, $texts[$sub]);
				$viewer = new text_general_HTML();
				$viewer->showTagList($taglist, $event, $texts, $taglist);
			}
		}
	}
		
}
