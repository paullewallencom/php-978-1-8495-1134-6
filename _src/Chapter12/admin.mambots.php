<?php

class mambotsAdminMambots extends aliroDBUpdateController {
	public $act = 'mambots';
	
	protected $session_var = 'cor_mambots_classid';
	protected $table_name = '#__mambots';
	protected $DBname = 'aliroCoreDatabase';
	protected $view_class = 'listMambotsHTML';
	public $list_exclude = array ('params');
	protected $function_exclude = array ('new', 'remove', 'apply');

	public static function getInstance ($manager) {
		if (self::$instance == null) self::$instance = new self($manager);
		return self::$instance;
	}

	public function publishTask () {
		$this->setPublished(1);
	}

	public function unpublishTask () {
		$this->setPublished(0);
	}

	public function editTask () {
		if ($this->id) {
			$this->setID($this->id);
			$mambot = new aliroMambot;
			$mambot->load($this->id);
			$params = aliroExtensionHandler::getInstance()->getParamsObject($mambot->params, $mambot->element);
			$view = new listMambotsHTML($this);
			$view->edit($this->id, $params, $mambot->published);
		}
		else $this->redirect('index.php?core=cor_mambots', T_('No plugin specified for editing'));
	}

	private function setPublished ($value) {
		$id = $this->getParam($_REQUEST, 'id', 0);
		$database = call_user_func(array($this->DBname, 'getInstance'));
		$database->doSQL ("UPDATE #__mambots SET published=$value WHERE id=$id");
		aliroMambotHandler::getInstance()->clearCache();
		$message = ($value ? T_('The plugin has been activated') : T_('The plugin has been deactivated'));
		$this->redirect($this->optionurl, $message);
	}

	public function saveTask ($justapply=false) {
		if ($id = $this->getID()) {
			$mambot = new aliroMambot;
			$mambot->load($id);
			$mambot->published = '0';
			if (!$mambot->bindOnly($_POST, 'published, params')) {
				$view = new listMambotsHTML($this);
				$view->edit($this->id, $params, $mambot->published);
			}
			$mambot->published = $mambot->published ? 1 : 0;
			$mambot->store();
			aliroMambotHandler::getInstance()->clearCache();
			if ($justapply) $this->redirect('index.php?core=cor_mambots&task=edit&id='.$id, T_('Plugin update saved'));
			$this->redirect('index.php?core=cor_mambots', T_('Plugin update saved'));
		}
		$this->redirect('index.php?core=cor_mambots', T_('Plugin update unexpected, no action taken'), _ALIRO_ERROR_WARN);
	}
	
	public function applyTask () {
		$this->saveTask(true);
	}

}