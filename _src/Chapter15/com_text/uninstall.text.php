<?php

class uninstalltext {

	public function __construct () {
		$database = aliroCoreDatabase::getInstance();
		$database->doSQL("DELETE FROM #__permissions WHERE subject_type = 'aSimpleText'");
	}
}