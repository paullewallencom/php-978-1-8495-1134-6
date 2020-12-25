<?php

class upgradetext {

	public function __construct () {
		$database = aliroDatabase::getInstance();
		$database->addFieldIfMissing('#__simple_text', 'folderid', "int(11) NOT NULL default 0 AFTER `id`");
		$database->addFieldIfMissing('#__simple_text', 'dfolderid', "int(11) NOT NULL default 0 AFTER `id`");
	}
}