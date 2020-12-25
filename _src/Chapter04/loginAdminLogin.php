<?php

class loginAdminLogin extends aliroComponentAdminControllers {

	private static $instance = null;

	public static function getInstance ($manager) {
		return is_object(self::$instance) ? self::$instance : (self::$instance = new self($manager));
	}

	public function getRequestData () {
		// Location for any extra request data to be acquired
	}

	public function checkPermission () {
		return $this->authoriser->checkUserPermission('manage', 'aUser', 0);
	}
	
	public function toolbar () {
		$toolbar = aliroAdminToolbar::getInstance();
		switch ($this->task) {
			case 'new':
			case 'edit':
			case 'editA':
				$toolbar->save();
				$toolbar->apply();
				$toolbar->cancel();
				$toolbar->help( '453.screen.users.edit' );
				break;

			case 'list':
			default:
				$toolbar->addNewX();
				$toolbar->editListX();
				$toolbar->deleteList();
				$toolbar->custom( 'logout', 'cancel.png', 'cancel_f2.png', '&nbsp;Force Logout' );
				$toolbar->help( '453.screen.users.main' );
				break;
		}
	}

	public function listTask () {
		$option = 'com_login';
		$database = aliroCoreDatabase::getInstance();
		$filter_type = $this->getUserStateFromRequest( "filter_type{$option}", 'filter_type');
		$filter_logged = $this->getUserStateFromRequest( "filter_logged{$option}", 'filter_logged', 0 );
		$intext = '';
		$where = array();
		if ($filter_type OR $filter_logged) {
			if ($filter_type) {
				$filter_type = $database->getEscaped($filter_type);
				$tablespec = '#__assignments AS a';
				$where[] = "a.access_type = 'aUser' AND a.role = '$filter_type'";
				$answers = 'a.access_id';
			}
			if ($filter_logged) {
				if ($filter_type) $tablespec = '#__assignments AS a INNER JOIN #__session AS s ON a.access_id = s.userid';
				else $tablespec = '#__session AS s';
				$where[] = 's.userid != 0';
				$answers = 's.userid';
			}
			$conditions = implode(' AND ', $where);
			$database->setQuery("SELECT $answers FROM $tablespec WHERE $conditions");
			$numbers = $database->loadResultArray();
			if ($numbers) {
				$list = implode(',', $numbers);
				$intext = "u.id IN ($list)";
			}
			else $intext = 'u.id < 0';
		}

		$database = aliroDatabase::getInstance();
		$where = $intext ? array($intext) : array();
		$search = $this->getUserStateFromRequest( "search{$option}", 'search', '' );
		if ($search) {
			$search = $database->getEscaped($search);
			$filter_by = $this->getUserStateFromRequest( "filter_by", 'filter_by', 'all');
			if (!in_array($filter_by, array ('all', 'name', 'username', 'email'))) $filter_by = 'all';
			if ($filter_by == "all") $where[] = "(u.username LIKE '%$search%' OR u.email LIKE '%$search%' OR u.name LIKE '%$search%')";
			else $where[] = "(u.$filter_by LIKE '%$search%')";
		}
		if ($conditions = implode(' AND ', $where )) $conditions = ' WHERE '.$conditions;
		$query = "SELECT %s FROM #__users AS u";

		$database->setQuery(sprintf($query,'COUNT(u.id)').$conditions);
		$total = $database->loadResult();
		$this->makePageNav($total);
		if ($total) {
			$limiter = " LIMIT {$this->pageNav->limitstart}, {$this->pageNav->limit}";
			$database->setQuery(sprintf($query,'u.*, u.usertype as groupname').$conditions.$limiter);
			$rows = $database->loadObjectList();
			if ($rows) {
				foreach ($rows as $row) $selid[] = $row->id;
				$selidlist = implode(',', $selid);
				$database = aliroCoreDatabase::getInstance();
				$database->setQuery("SELECT userid FROM #__session WHERE userid IN ($selidlist)");
				$loggedids = $database->loadResultArray();
				$adminsite = aliroCore::getInstance()->getCfg('admin_site');
				foreach ($rows as $key=>$row) {
					if ($loggedids AND in_array($row->id, $loggedids)) $rows[$key]->loggedin = <<<LOG_IMAGE

					<img src="$adminsite/images/tick.png" width="12" height="12" border="0" alt="" />
LOG_IMAGE;

					else $rows[$key]->loggedin = '';
				}
			}
			else $rows = array();
		}
		else $rows = array();

		$fixHTML = aliroHTML::getInstance();
		$types[] = $fixHTML->makeOption( '0', '- Select Roles -' );
		$roles = $this->authoriser->getAllRoles();
		foreach ($roles as $role) $types[] = $fixHTML->makeOption($role);
		$lists['type'] = $fixHTML->selectList( $types, 'filter_type', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_type" );
		//$lists['type'] = $fixHTML->selectList( $types, 'filter_type', 'class="inputbox" size="1"', 'value', 'text', "$filter_type" );

	    // list of criteria filters
		$filterby[] = $fixHTML->makeOption( 'all', '- All fields -');
		$filterby[] = $fixHTML->makeOption( 'name', 'Name');
		$filterby[] = $fixHTML->makeOption( 'username', 'userID');
		$filterby[] = $fixHTML->makeOption( 'email', 'e-mail');
		$filter_by = $this->getUserStateFromRequest( "filter_by", 'filter_by', 'all');
		$lists['filter_by'] = $fixHTML->selectList( $filterby, 'filter_by', 'class="inputbox" size="1"', 'value', 'text', $filter_by );

		// get list of Log Status for dropdown filter
		$logged[] = $fixHTML->makeOption( 0, '- Select Log Status - ');
		$logged[] = $fixHTML->makeOption( 1, 'Logged In');
		$lists['logged'] = $fixHTML->selectList( $logged, 'filter_logged', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_logged" );
		// Create and activate a View object
		$view = new loginAdminListHTML ($this);
		$view->view($rows, $lists, $search);
	}

	public function newTask () {
		$row = new mosUser();
		$fixHTML = aliroHTML::getInstance();

		$subject_roles = $this->getSubjectRoles();
		$role_option = array();
		foreach ($subject_roles as $role) {
			$role_option[] = $fixHTML->makeOption($role, $role);
		}
		$lists['gid'] = $fixHTML->selectList( $role_option, 'gid[]', 'multiple="multiple"', 'value', 'text' );

		// build the html select list
		$lists['block'] = $fixHTML->yesnoRadioList( 'block', 'class="inputbox" size="1"', $row->block );
		// build the html select list
		$lists['sendEmail'] = $fixHTML->yesnoRadioList( 'sendEmail', 'class="inputbox" size="1"', $row->sendEmail );

		$standard = array('id', 'name', 'username', 'email', 'usertype', 'block', 'sendEmail', 'gid', 'registerDate', 'lastvisitDate', 'params');
		$quickhtml = new aliroQuickHTML('Title{title:30}Location{location:30}');
		$quickhtml->setBlockedNames($standard);

		$view = new loginAdminEditHTML ($this);
		$view->edituser($row, null, $lists, $this->option, 0, $quickhtml);
	}
	
	private function getSubjectRoles () {
		if (in_array('Super Administrator', $this->authoriser->getAccessorRoles('aUser', $this->user->id))) {
			return $this->authoriser->getAllRoles();
		}
		else return $this->authoriser->listUserPermissions('administer');
	}

	public function editTask () {
		if (isset($this->cid[0])) {
			$this->idparm = $this->cid[0];
			$this->editaTask();
		}
		else {
			$msg = T_('No user specified for edit');
			$this->redirect('index.php?option=com_login', $msg);
		}
	}
	
	public function editaTask () {
		$row = new mosUser;
		$row->load($this->idparm);
		$subject_roles = $this->getSubjectRoles();
		$role_option = array();
		$fixHTML = aliroHTML::getInstance();
		foreach ($subject_roles as $role) {
			$role_option[] = $fixHTML->makeOption($role, $role);
		}
		$user_roles = $this->authoriser->getAccessorRoles('aUser', $this->idparm);
		foreach ($user_roles as $key=>$role) if (!in_array($role,$subject_roles)) unset ($user_roles[$key]);
		$lists['gid'] = $fixHTML->selectList( $role_option, 'gid[]', 'multiple="multiple"', 'value', 'text', $user_roles);

		// build the html select list
		$lists['block'] = $fixHTML->yesnoRadioList( 'block', 'class="inputbox" size="1"', $row->block );
		// build the html select list
		$lists['sendEmail'] = $fixHTML->yesnoRadioList( 'sendEmail', 'class="inputbox" size="1"', $row->sendEmail );
		
		$standard = array('id', 'name', 'username', 'email', 'usertype', 'block', 'sendEmail', 'gid', 'registerDate', 'lastvisitDate', 'params');
		$quickhtml = new aliroQuickHTML('Title{title:30}Location{location:30}');
		$quickhtml->setBlockedNames($standard);

		$view = new loginAdminEditHTML ($this);
		$view->edituser($row, null, $lists, $this->option, $this->idparm, $quickhtml);
	}

	public function saveTask () {
		$row = $this->commonSave();
		$message = is_object($row) ? T_('Successfully Saved User: ').$row->name : $this->pullLastMostSevereMessage();
		$this->redirect('index.php?option=com_login', $message);
	}

	public function applyTask () {
		$row = $this->commonSave();
		$message = is_object($row) ? T_('Successfully Saved changes to User: ').$row->name : $this->pullLastMostSevereMessage();
		$this->redirect('index.php?option=com_login&task=editA&hidemainmenu=1&id='.$this->idparm, $message);
	}

	private function passwordMismatch ($p1, $p2) {
		if ($p1 != $p2) {
			$this->setErrorMessage(T_('Passwords not the same, operation abandoned'), _ALIRO_ERROR_SEVERE);
			return true;
		}
		return false;
	}

	private function commonSave () {
		$database = aliroDatabase::getInstance();
		$row = new mosUser();
		if ($this->idparm) $row->load($this->idparm);
		$row->bind($_POST);
		$row->gid = $this->getParam($_POST, 'gid');
		$password = $this->getParam($_POST, 'password');
		$password2 = $this->getParam($_POST, 'password2');
		// MD5 hash convert passwords
		if ($isNew = !$row->id) {
			// new user stuff
			if ($this->passwordMismatch ($password, $password2)) $this->newTask();
			$authenticator = aliroUserAuthenticator::getInstance();
			$pwd = $password ? $password : $authenticator->makePassword();
			$password = $database->getEscaped($pwd);
			$row->registerDate = date( 'Y-m-d H:i:s' );
		}
		else {
			// existing user stuff
			if ($this->passwordMismatch ($password, $password2)) $this->editATask();
			$rowid = $row->id;
		}

		// This next section is purely for backwards compatibility
		$groupids = array ('Super Administrator' => 25, 'Administrator' => 24, 'Manager' => 23,
		'Publisher' => 21, 'Editor' => 20, 'Author' => 19, 'Registered' => 18);

		$groupnum = 18;
		$groupname = 'Registered';
		foreach ($row->gid as $role) {
			if (isset($groupids[$role]) AND $groupids[$role] > $groupnum) {
				$groupname = $role;
				$groupnum = $groupids[$role];
			}
		}
		$row->gid = $groupnum;
		$row->usertype = $groupname;
		// End of compatibility stuff

	 	// save usertype to usetype column
		$row->usertype = $groupname;
		$row->gid = $groupnum;

		// Need non-JS error handling
		if (!$row->check()) return null;
		$row->userStore($password);
		$row->checkin();
		$ACLadmin = aliroAuthorisationAdmin::getInstance();
		if (isset($_POST['gid']) AND count($_POST['gid'])) $roleset = $_POST['gid'];
		else $roleset = array($groupname);
		$extrarole = $this->getParam($_POST, 'extrarole');
		if ($extrarole) $roleset[] = $extrarole;
		$ACLadmin->assignRoleSet($roleset, 'aUser', $row->id);

		// for new users, email username and password
		if ($isNew) {
			$my = aliroUser::getInstance();
			$database->setQuery("SELECT email FROM #__users WHERE id=$my->id");
			$adminEmail = $database->loadResult();

			$subject = T_('New User Details');
			$newusermsg = T_('Hello %s,


You have been added as a user to %s by an Administrator.

This email contains your username and password to log into the %s

Username - %s
Password - %s


Please do not respond to this message as it is automatically generated and is for information purposes only');
			$message = sprintf ($newusermsg, $row->name, $this->getCfg('sitename'), $this->getCfg('live_site'), $row->username, $pwd );

			if ($this->getCfg('mailfrom') != "" AND $this->getCfg('fromname') != "") {
				$adminName = $this->getCfg('fromname');
				$adminEmail = $this->getCfg('mailfrom');
			} else {
				$query = "SELECT name, email FROM #__users WHERE usertype='superadministrator'";
				$database->setQuery( $query );
				$rows = $database->loadObjectList();
				$row = $rows[0];
				$adminName = $row->name;
				$adminEmail = $row->email;
			}
			$mail = new mosMailer ($adminEmail, $adminName, $subject, $message);
			$result = $mail->mosMail($row->email);
		}
		return $row;
	}

	function removeTask () {
		if (!is_array($this->cid) OR count ($this->cid) < 1) {
			$this->setMessage (T_('Select an item to delete'));
			$this->listTask();
		}
		$obj = new mosUser();
		$msg = T_('Requested deletions completed');
		foreach ($this->cid as $id) {
			$roles = $this->authoriser->getAccessorRoles('aUser', $id);
			$roles = $this->authoriser->minimizeRoleSet ($roles);
			foreach ($roles as $role) if (!$this->authoriser->checkUserPermission('administer', $role, 0)) {
				$msg = 'You cannot delete someone at or above your own level';
				$id = 0;
				break;
			}
			if ($id) {
				$obj->delete($id);
        		$authoriserAdmin = aliroAuthorisationAdmin::getInstance();
        		$authoriserAdmin->dropAccess('aUser', $id);
			}
		}
		$this->redirect ('index.php?option='.$this->option, $msg);
	}

	function blockTask () {
		$this->fixBlock (99, T_('block'));
	}

	function unblockTask () {
		$this->fixBlock (0, T_('unblock'));
	}

	private function fixBlock ($block, $action) {
		if (count( $this->cid ) < 1) {
			$this->setMessage(sprintf(T_('Please select one or more items to %s', $action)));
			return;
		}
		foreach ($this->cid as $key=>$cid) $cidarray[$key] = intval($cid);
		$cids = implode(',', $cidarray);
		$query = "UPDATE #__users SET block='$block' WHERE id IN ($cids)";
		$database = aliroDatabase::getInstance();
		$database->doSQL( $query );
		$this->listTask();
	}

	function logoutTask () {
		if (!is_array($this->cid) OR count ($this->cid) < 1) {
			$this->setMessage (T_('Select an item to logout'));
			$this->listTask();
		}
		$obj = new mosUser();
		$msg = T_('Requested logouts completed');
		$inlist = implode(',', $this->cid);
		$query = "DELETE FROM #__session WHERE userid IN ($inlist)";
		$database = aliroCoreDatabase::getInstance();
		$database->doSQL( $query );
		$this->redirect ('index.php?option='.$this->option, $msg);
	}

	function cancelTask () {
		$this->redirect('index.php?option=com_login');
	}

}