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

// This the controller for the "display" task (default in this case)
// As more tasks are implemented, more controllers will be needed.
// They can be located in separate files rather than being included here
//	so long as the packaging XML records the location of classes in files
class text_display_Controller extends textUserControllers {

	private static $instance = null;

	// The controller should be a singleton
	public static function getInstance ($manager) {
		return is_object(self::$instance) ? self::$instance : (self::$instance = new self($manager));
	}

	// Note that the "alternatives" array may have caused other control codes to be processed here
	// The actual value of the control variable is received as a parameter
	// The code here is just a simple illustration
	public function display ($task, $parmid=0, $tagnames='', $dlist='') {
		// Do whatever processing is required
		$viewer = new text_general_HTML();
		$id = $parmid ? $parmid : $this->getParam($_REQUEST, 'id', 0);
		$text = $this->getText($id);
		if (!$text) {
			new aliroPage404();
			return;
		}
		$event = ('yes' == $this->getParam($_REQUEST, 'blog')) ? 'onIntroText' : 'onMainText';
		aliroMambotHandler::getInstance()->trigger($event, array($text));
		aliroDatabase::getInstance()->doSQL("UPDATE #__simple_text SET hits = hits + 1 WHERE id = $id");
		if ($text->metakey) $this->addMetaTag('keywords', $text->metakey);
		if ($text->metadesc) $this->addMetaTag('description', $text->metadesc);
		$pathway = aliroPathway::getInstance();
		$pathway->reduceByOne();
		if (!$this->isHome()) {
			$this->folderPathway($text->folderid);
			if (!empty($tagnames)) {
				if ($dlist) {
					if (strpos($dlist, ',')) $link = 'index.php?option=com_text&task=multiple&ids='.$dlist;
					else $link = 'index.php?option=com_text&task=display&id='.$dlist;
				}
				else $link = '#';
				$taglist = implode(' + ', $tagnames);
				$pathway->addItem($taglist, $link);
			}
			$pathway->addItem($text->headline);
		}
		$this->setPageTitle($text->headline);
		$viewer->view($text);
	}
	
	private function folderPathway ($folderid) {
		if ($folderid) {
			$folder = aliroFolderHandler::getInstance()->getBasicFolder($folderid);
			if ($folder->parentid) $this->folderPathway($folder->parentid);
			$database = aliroDatabase::getInstance();
			$database->setQuery("SELECT id FROM #__simple_text WHERE dfolderid = $folder->id");
			$url = ($id = $database->loadResult()) ? aliroSEF::getInstance()->sefRelToAbs('index.php?option=com_text&task=display&id='.$id) : '';
			aliroPathway::getInstance()->addItem($folder->name, $url);
		}
	}
	
	public function displayForModule ($task, $id) {
		$text = $this->getText($id);
		if (!$text) return '';
		$viewer = new text_general_HTML();
		aliroMambotHandler::getInstance()->trigger('onMainText', array($text));
		aliroDatabase::getInstance()->doSQL("UPDATE #__simple_text SET hits = hits + 1 WHERE id = $id");
		return $viewer->textForView($text);
	}
	
	private function getText ($id) {
		$id = intval($id);
		$texts = aliroDatabase::getInstance()->doSQLget("SELECT * FROM #__simple_text WHERE published !=0 AND publish_start < NOW() "
		."AND (publish_end = '0000-00-00 00:00:00' OR publish_end > NOW()) AND id = $id", 'textItem');
		return (0 == count($texts) ? '' : $texts[0]);
	}

}
