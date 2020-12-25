<?php

class installtext {

	public function __construct () {
		$database = aliroCoreDatabase::getInstance();
		$database->doSQL("INSERT INTO #__permissions (role, control, action, subject_type, subject_id) VALUES('Super Administrator', 3, 'manage', 'aSimpleText', '*')");
		$database->doSQL("INSERT INTO #__permissions (role, control, action, subject_type, subject_id) VALUES('Super Administrator', 3, 'edit', 'aSimpleText', '*')");
		aliroDatabase::getInstance()->addFieldIfMissing('#__simple_text', 'dfolderid', 'int(11) NOT NULL default 0 AFTER `folderid`');
	}
}