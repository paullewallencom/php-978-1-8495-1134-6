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

// Because most information is taken from the database, only the principal
// details need to be given in the class data declaration.
// However, it can be made much more powerful by adding problem specific methods.
class textItem extends aliroDatabaseRow {
	protected $DBclass = 'aliroDatabase';
	protected $tableName = '#__simple_text';
	protected $rowKey = 'id';
	
	public function store ($updateNulls=false) {
		$userid = aliroUser::getInstance()->id;
		if ($this->id) {
			$this->modified = date('Y-m-d H:i:s');
			$this->modify_id = $userid;
		}
		else {
			$this->created = date('Y-m-d H:i:s');
			$this->author_id = $userid;
		}
		parent::store($updateNulls);
	}
	
	public function getText () {
		return $this->article;
	}
	
	public function saveText ($text) {
		$this->article = $text;
	}
	
	public function getID () {
		return $this->id;
	}

}