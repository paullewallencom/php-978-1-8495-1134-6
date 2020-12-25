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

// This is the default controller class for the component
// If actions are specified by setting "act" through a menu or control panel,
//	other classes will be needed.  If the value of "act" is xxx, then the class
//	will need to be called textAdminXxx.
// The controller class must implement methods for the various tasks that
//	are available via the toolbar.
// The getRequestData method will be called if it exists - before the toolbar method
// If a toolbar method is provided it will be used in preference to trying to load toolbar files.
class textAdminText extends aliroComponentAdminControllers {
	private static $instance = null;
	public $act = 'text';

	// If no code is needed in the constructor, it can be omitted, relying on the parent class
	protected function __construct ($manager) {
		parent::__construct ($manager);
	}

	public static function getInstance ($manager) {
		return is_object(self::$instance) ? self::$instance : (self::$instance = new self ($manager));
	}

	public function getRequestData () {
		// Get information from $_POST or $_GET or $_REQUEST
		// This method will be called before the toolbar method
	}

	// If this method is provided, it should return true if permission test is satisfied, false otherwise
	public function checkPermission () {
		$authoriser = aliroAuthoriser::getInstance();
		if ($authoriser->checkUserPermission('manage', 'aSimpleText', '*')) {
			if ($this->idparm) {
				if ($authoriser->checkUserPermission('edit', 'aSimpleText', $this->idparm)) return true;
			}
			else return true;
		}
		return false;
	}

	// The code that creates the toolbar
	public function toolbar () {
		switch ( $this->task ) {
			case 'new':
			case 'edit':
				$this->toolbarEDIT();
				break;

			case 'cancel':
			case 'save':
			default:
				$this->toolbarDEFAULT();
				break;
		}
	}

	// Should be specific to the component's design
	private function toolbarEDIT() {
		// Set up the toolbar for editing
		$toolbar = aliroAdminToolbar::getInstance();
		$toolbar->save();
		$toolbar->cancel();
	}

	// The default admin page is often a list of items, with a toolbar something like:
	private function toolbarDEFAULT() {
		$toolbar = aliroAdminToolbar::getInstance();
		$toolbar->publish();
		$toolbar->unpublish();
		$toolbar->addNew();
		$toolbar->editList();
		$toolbar->deleteList();
	}

	// This is the default action, and will list some items from the database, with page control
	public function listTask () {
		$database = aliroDatabase::getInstance();

		// get the total number of records
		$database->setQuery("SELECT COUNT(*) FROM #__simple_text");
		$total = $database->loadResult();
		$this->makePageNav($total);

		// get the subset (based on limits) of required records
		$query = "SELECT * FROM #__simple_text "
		. "\n LIMIT {$this->pageNav->limitstart}, {$this->pageNav->limit}"
		;
		$rows = $database->doSQLget($query, 'textItem');

		// Check whether reordering can move this line up or down
		// Remove this code if ordering is not used
		foreach ($rows as $i=>&$row) {
			$row->upok = isset($rows[$i-1]);
			$row->downok = isset($rows[$i+1]);
		}
		
		$view = new listTextHTML ($this);
		$view->view($rows, $this->fulloptionurl);
	}
	
	public function cancelTask () {
		$this->listTask();
	}

	// This code is likely to be application specific
	public function editTask () {
		$id = $this->idparm ? $this->idparm : $this->currid;
		if (!$id) {
			$this->listTask();
			return;
		}
		$database = aliroDatabase::getInstance();
		$database->setQuery("SELECT tag_id FROM #__simple_text_tags WHERE text_id = $id AND describes = 0");
		$tagtexts = $database->loadResultArray();
		if (!$tagtexts) $tagtexts = array();
		$database->setQuery("SELECT tag_id FROM #__simple_text_tags WHERE text_id = $id AND describes != 0");
		$tagheads = $database->loadResultArray();
		if (!$tagheads) $tagheads = array();
		$text = new textItem();
		$text->load($id);
		$clist = aliroFolderHandler::getInstance()->getSelectList(true, $text->folderid, 'folderid', '', $this->user);
		$clistd = aliroFolderHandler::getInstance()->getSelectList(true, $text->dfolderid, 'dfolderid', '', $this->user);
		$view = new listTextHTML ($this);
		$view->edit($text, $tagtexts, $tagheads, $clist, $clistd);
	}
	
	public function newTask () {
		$text = new textItem();
		$clist = aliroFolderHandler::getInstance()->getSelectList(true, $text->folderid, 'folderid', '', $this->user);
		$clistd = aliroFolderHandler::getInstance()->getSelectList(true, $text->folderid, 'dfolderid', '', $this->user);
		$view = new listTextHTML ($this);
		$view->edit($text, array(), array(), $clist, $clistd);
	}
	
	public function saveTask () {
		$text = new textItem();
		if ($id = $this->getParam($_REQUEST, 'id', 0)) $text->load($id);
		$text->published = 0;
		$text->bind($_POST);
		$text->store();
		$database = aliroDatabase::getInstance();
		$database->doSQL( "DELETE FROM #__simple_text_tags WHERE text_id = $text->id" );
		$tags = $this->getParam($_REQUEST, 'tags', array());
		foreach ($tags as $tagid) if ($tagid) $database->doSQL("INSERT INTO #__simple_text_tags VALUES ($text->id, $tagid, 0)");
		$tags = $this->getParam($_REQUEST, 'describes', array());
		foreach ($tags as $tagid) if ($tagid) $database->doSQL("INSERT INTO #__simple_text_tags VALUES ($text->id, $tagid, 1)");
		$this->listTask();
	}

	// If the component handles a list of items, a selection can be deleted using code like this
	public function removeTask () {
		$database = aliroDatabase::getInstance();
		if (count($this->cid)) {
			foreach ($this->cid as &$id) $id = intval($id);
			$cids = implode( ',', $this->cid );
			$database->doSQL( "DELETE FROM #__simple_text WHERE id IN ($cids)" );
			$database->doSQL( "DELETE FROM #__simple_text_tags WHERE text_id IN ($cids)" );
		}
		$this->redirect('index.php?option=com_text', T_('Deletion completed'));
	}

	public function publishTask () {
		$this->changePublished(1);
	}

	public function unpublishTask() {
		$this->changePublished(0);
	}

	// Code is needed specific to the component for the ordering methods
	// Models can be found in classes such as aliroMenuHandler
	public function orderupTask () {
		$this->redirect('index.php?option=com_text', T_('Ordering not implemented in this model component'));
	}

	public function orderdownTask () {
		$this->redirect('index.php?option=com_text', T_('Ordering not implemented in this model component'));
	}

	public function saveorderTask () {
		$this->redirect('index.php?option=com_text', T_('Ordering not implemented in this model component'));
	}

	// This method does the real work for publish and unpublish
	private function changePublished ($state=0) {
		$database = aliroDatabase::getInstance();
		if (count($this->cid)) {
			foreach ($this->cid as &$id) $id = intval($id);
			$new_publish = intval($state);
			$idlist = implode (',', $this->cid);
			$sql = "UPDATE #__simple_text SET published = $new_publish WHERE id IN ($idlist)";
			$database->doSQL ($sql);
		}
		$this->redirect('index.php?option=com_text');
	}

	// Other Task methods may be needed, depending on what is in the toolbar

}