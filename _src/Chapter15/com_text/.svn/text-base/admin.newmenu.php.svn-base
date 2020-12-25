<?php

// Display code must avoid setting up input tags using the names id, task, cid, order, menuselect, menutype
// These are used by the menu manager core component, and using them will cause confusion

class simpleTextMenu extends aliroFriendlyBase {
	private $controller = null;

	public function perform ($mystuff, $controller) {
		$this->controller = $controller;
		$method = 'processStage'.$mystuff->stage;
		if (method_exists($this, $method)) $this->$method($mystuff);
		else trigger_error(T_('Invalid stage indicator in Simple Text menu creation class'));
	}
	
	private function processStage0 ($mystuff) {
		$mystuff->html = $this->selectType();
		$mystuff->link = '';
		$mystuff->xmlfile = '';
		$this->intermediate($mystuff, 1);
	}
	
	private function processStage1 ($mystuff) {
		$type = $this->getParam($_REQUEST, 'textfunc');
		switch ($type) {
			case 'basic':
				$this->processStage2 ($mystuff);
				break;
			case 'taglist':
				$this->processStage12 ($mystuff);
				break;
			case 'tagged':
				$this->processStage22 ($mystuff);
				break;
			default:
				$this->processStage0 ($mystuff);
				break;
		}
	}
	
	private function processStage2 ($mystuff) {
		// Initial entry - user must choose an article
		$articles = aliroDatabase::getInstance()->doSQLget ("SELECT id, headline, byline, article FROM `#__simple_text`");
		$mystuff->html = $this->selectArticle($articles);
		$this->intermediate($mystuff, 3);
	}

	private function processStage3 ($mystuff) {
		// Text now chosen - finished
		$id = $this->getParam($_REQUEST, 'textid', 0);
		$text = new textItem();
		$text->load($id);
		if ($text->id) {
			$mystuff->link = "index.php?option=com_text&task=display&id=$text->id";
			$mystuff->name = $text->headline;
			$mystuff->html = $this->menuSummary($mystuff);
		}
		else $this->processStage2($mystuff);
	}
	
	private function processStage12 ($mystuff) {
		$viewer = new listTextHTML($this->controller);
		$text_header = T_('Please choose one or more tags to define the list');
		$mystuff->html = <<<TAG_SELECT
		
			<h3>$text_header</h3>
			{$viewer->makeTagList(array(), 'tags', false)}
TAG_SELECT;

		$this->intermediate($mystuff, 13);
	}
	
	private function processStage13 ($mystuff) {
		$tags = $this->getParam($_REQUEST, 'tags', array());
		if (empty($tags)) $this->processStage12($mystuff);
		else {
			foreach ($tags as &$tag) $tag = intval($tag);
			$taglist = implode(',', $tags);
			$mystuff->link = "index.php?option=com_text&task=taglist&tags=$taglist";
			$mystuff->name = T_('List by tag');
			$mystuff->html = $this->selectIsBlog();
			$this->intermediate($mystuff, 14);
		}
	}
	
	private function processStage14 ($mystuff) {
		// Text now chosen - finished
		$blog = $this->getParam($_REQUEST, 'isblog', 0);
		if ($blog) $mystuff->link .= '&blog=yes';
		$mystuff->html = $this->menuSummary($mystuff);
	}
	
	private function processStage22 ($mystuff) {
		$viewer = new listTextHTML($this->controller);
		$text_header = T_('Please choose one or more tags to define the list');
		$mystuff->html = <<<TAG_SELECT
		
			<h3>$text_header</h3>
			{$viewer->makeTagList(array(), 'tags', false)}
TAG_SELECT;

		$this->intermediate($mystuff, 23);
	}
	
	private function processStage23 ($mystuff) {
		$tags = $this->getParam($_REQUEST, 'tags', array());
		if (empty($tags)) {
			$this->processStage22($mystuff);
			return;
		}
		foreach ($tags as &$tag) $tag = intval($tag);
		$taglist = implode(',', $tags);
		// Initial entry - user must choose an article
		$articles = aliroDatabase::getInstance()->doSQLget ("SELECT t.id, t.headline, t.byline, t.article FROM `#__simple_text` AS t INNER JOIN #__simple_text_tags AS g ON t.id = g.text_id WHERE g.tag_id IN ($taglist)");
		$mystuff->html = $this->selectArticle($articles);
		$this->intermediate($mystuff, 24);
		$mystuff->link = $taglist;
	}
	
	private function processStage24 ($mystuff) {
		// Text now chosen - finished
		$taglist = $mystuff->link;
		$id = $this->getParam($_REQUEST, 'textid', 0);
		$text = new textItem();
		$text->load($id);
		if ($text->id) {
			$mystuff->link = "index.php?option=com_text&task=inlist&id=$text->id&tags=$taglist";
			$mystuff->name = $text->headline;
			$mystuff->html = $this->menuSummary($mystuff);
		}
		else $this->processStage23($mystuff);
	}
	
	private function intermediate ($mystuff, $nextstage) {
		$mystuff->stage = $nextstage;
		$mystuff->save = false;
		$mystuff->finished = false;
	}
	
	private function selectType () {
		$text_header = T_('Please choose the type of display');
		$basic = T_('Display a single simple text item');
		$taglist = T_('Display a list of items by tag');
		$tagged = T_('Display a single item that is part of a tagged group');
		return <<<SELECT_TYPE
		
			<h3>$text_header</h3>
			<input type="radio" name="textfunc" value="basic" class="inputbox" checked="checked" />$basic<br />
			<input type="radio" name="textfunc" value="tagged" class="inputbox" />$tagged<br />
			<input type="radio" name="textfunc" value="taglist" class="inputbox" />$taglist
		
SELECT_TYPE;

	}

	private function selectArticle ($texts) {
		$this->addCSS(_ALIRO_ADMIN_DIR.'/components/com_text/admin.text.css');
		$text_header = T_('Please choose a text item: ');
		$optionhtml = '';
		foreach ($texts as $text) {
			$optionhtml .= <<<TEXT_ITEM

			<option value="$text->id">{$this->plainText($text->headline,50)} / {$this->plainText($text->byline,50)} / {$this->plainText($text->article,50)}</option>

TEXT_ITEM;

		}
		return <<<CHOOSE_TEXT

		<h3>$text_header</h3>
		<select name="textid" class="inputbox">
		$optionhtml
		</select>

CHOOSE_TEXT;

	}
	
	private function selectIsBlog () {
		$this->addCSS(_ALIRO_ADMIN_DIR.'/components/com_text/admin.text.css');
		$text_header = T_('Do you want a list of headings or a list of texts (blog style)?: ');
		$option1 = T_('List headings as links to articles');
		$option2 = T_('List articles in full, blog style');
		return <<<CHOOSE_TEXT

		<h3>$text_header</h3>
		<select name="isblog" class="inputbox">
			<option value="0">$option1</option>
			<option value="1">$option2</option>
		</select>

CHOOSE_TEXT;
		
	}

	private function menuSummary ($mystuff) {
		$mystuff->stage = 99;
		$mystuff->finished = true;
		$mystuff->save = true;
		return 'Finished - nothing to display - this is an error condition';
	}

	private function plainText ($text, $length) {
		$dots = strlen($text) > $length-3 ? '...' : '';
		$plain = strip_tags($text);
		return $dots ? substr($plain, 0, $length-3).$dots : $plain;
	}

}