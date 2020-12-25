<?php

abstract class aliroDBUpdateController extends aliroComponentAdminControllers {

	protected static $instance = null;
	protected $cid = array();
	protected $id = 0;

	public function getRequestData () {
		$this->cid = $this->getParam($_REQUEST, 'cid', array());
		$this->id = $this->getParam($_REQUEST, 'id', 0);
		if (!$this->id AND isset($this->cid[0])) $this->id = intval($this->cid[0]);
	}

	public function checkPermission () {
		return true;
	}

	protected function setID ($id) {
		if ($id) $_SESSION[$this->session_var] = $id;
	}

	protected function getID () {
		if (isset($_SESSION[$this->session_var])) return $_SESSION[$this->session_var];
		else return 0;
	}

	protected function clearID () {
		if (isset($_SESSION[$this->session_var])) unset ($_SESSION[$this->session_var]);
	}

	public function listTask () {
		$database = call_user_func(array($this->DBname, 'getInstance'));
		$query = "SELECT %s FROM $this->table_name";
		if (isset($this->limit_list)) $query .= ' WHERE '.$this->limit_list;
		$database->setQuery(sprintf($query, 'COUNT(*)'));
		$total = $database->loadResult();
		$this->makePageNav($total);
		if ($total) {
			$limiter = " LIMIT {$this->pageNav->limitstart}, {$this->pageNav->limit}";
			$database->setQuery(sprintf($query,'*').$limiter);
			$rows = $database->loadObjectList();
		}
		else $rows = array();
		$view = new $this->view_class ($this);
		$view->view($rows);
	}

	public function newTask () {
		if ($this->checkExclusion('new')) return;
		$this->clearID();
		$view = new $this->view_class ($this);
		$view->newclass();
	}

	public function editTask () {
		if ($this->checkExclusion('edit')) return;
		if ($this->id) {
			$this->setID($this->id);
			$database = call_user_func(array($this->DBname, 'getInstance'));
			$database->setQuery("SELECT * FROM $this->table_name WHERE id=$this->id");
			$database->loadObject($classdocs);
			$view = new $this->view_class ($this);
			$view->editclass($classdocs);
		}
		else $this->setErrorMessage(T_('No item was selected for editing'));
	}

	public function saveTask () {
		if ($this->checkExclusion('save')) return;
		$this->commonSave();
		$this->clearID();
		$this->redirect($this->optionurl);
	}

	public function applyTask () {
		if ($this->checkExclusion('apply')) return;
		$this->commonSave();
		$this->redirect($this->optionurl.'&task=edit&id='.$this->getID());
	}

	protected function commonSave () {
		$id = $this->getID();
		if ($id) $this->basicUpdate($this->table_name, 'id', $id);
		else {
			$newid = $this->basicInsert($this->table_name);
			$this->setID($newid);
		}
	}

	public function removeTask () {
		if ($this->checkExclusion('remove')) return;
		if (count($this->cid)) {
			foreach ($this->cid as &$id) $id = intval($id);
			$idlist = implode(',', $this->cid);
			$database = call_user_func(array($this->DBname, 'getInstance'));
			$database->doSQL("DELETE FROM $this->table_name WHERE id IN ($idlist)");
			$this->setErrorMessage(T_('Deletion completed'), _ALIRO_ERROR_INFORM);
		}
		else $this->setErrorMessage(T_('No items for deletion'), _ALIRO_ERROR_WARN);
		$this->redirect($this->optionurl);
	}

	public function cancelTask () {
		$this->clearID();
		$this->redirect($this->optionurl);
	}

}