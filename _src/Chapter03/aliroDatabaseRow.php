<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the 
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more 
 * information on this, please see the index.php file or visit 
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * Classes to do with building objects linked to database rows
 *
 */

// Not currently used, but provides a way to create a data object when the class is a variable
// rather than a constant
class aliroDBRowFactory {

	static public function makeObject ($classname, $key=null) {
		if (is_subclass_of($classname, 'aliroDBGeneralRow')) {
			$object = new $classname;
			if (!empty($key)) $object->load($key);
			return $object;
		}
		else trigger_error(T_('Asked aliroDBRowFactory to create object not subclassed from aliroDBGeneralRow'));
	}
}

// This is the general database row handling class, extended by other classes below
abstract class aliroDBGeneralRow {
	public $_error = '';

	protected function T_($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	function getError() {
		return $this->_error;
	}

	public function check() {
		return true;
	}

	public function getDatabase () {
		return call_user_func(array($this->DBclass, 'getInstance'));
	}

	public function getNumRows( $cur=null ) {
		return $this->getDatabase()->getNumRows($cur);
	}

	public function getAffectedRows () {
		return $this->getDatabase()->getAffectedRows();
	}

	public function insert () {
		return $this->getDatabase()->insertObject($this->tableName, $this, $this->rowKey);
	}

	public function update ($updateNulls=true) {
		return $this->getDatabase()->updateObject($this->tableName, $this, $this->rowKey, $updateNulls);
	}

	public function load( $key=null ) {
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		if (empty($this->$k)) return false;
		$this->getDatabase()->setQuery("SELECT * FROM $this->tableName WHERE $this->rowKey='{$this->$k}'" );
		return $this->getDatabase()->loadObject($this);
	}

	public function store( $updateNulls=false ) {
		$k = $this->rowKey;
		$ret = $this->$k ? $this->update($updateNulls) : $this->insert();
		if (!$ret) $this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->getDatabase()->getErrorMsg();
		return $ret;
	}

	public function storeNonAuto ($updateNulls=false, $ignore=false) {
		$this->getDatabase()->insertOrUpdateObject($this->tableName, $this, $this->rowKey, $updateNulls, $ignore);
	}

	public function insertNonAuto () {
		$this->getDatabase()->insertObjectSafely($this->tableName, $this);
	}

	public function bind( $objectorarray, $ignore='', $strip=true ) {
		$fields = $this->getDatabase()->getAllFieldNames ($this->tableName);
		foreach ($fields as $key=>$field) if (false !== strpos($ignore, $field)) unset($fields[$key]);
		return $this->bindDoWork ($objectorarray, $fields, $strip);
	}

	public function bindOnly ($objectorarray, $accept='', $strip=true) {
		$fields = $this->getDatabase()->getAllFieldNames ($this->tableName);
		foreach ($fields as $key=>$field) if (false === strpos($accept, $field)) unset($fields[$key]);
		return $this->bindDoWork ($objectorarray, $fields, $strip);
	}

	private function bindDoWork ($objectorarray, $fields, $strip) {
		if (is_array($objectorarray) OR is_object($objectorarray)) {
			foreach ($fields as $field) {
				$data = is_array($objectorarray) ? @$objectorarray[$field] : @$objectorarray->$field;
				if (is_string($data)) {
					$this->$field = $strip ? $this->stripMagicQuotes($data) : $data;
					if ('params' != $field AND (false !== strpos($this->$field, '&') OR false !== strpos($this->$field, '<'))) {
						$this->$field = $this->doPurify($this->$field);
					}
				}
			}
			return true;
		}
		$this->_error = strtolower(get_class($this)).$this->T_('::bind failed, parameter not an array');
		return false;
	}

	protected function doPurify ($field) {
		return aliroRequest::getInstance()->doPurify($field);
	}

	private function stripMagicQuotes ($field) {
		return (get_magic_quotes_gpc() AND is_string($field)) ? stripslashes($field) : $field;
	}

	public function lacks( $property ) {
		if (in_array($property, $this->getDatabase()->getAllFieldNames($this->tableName))) return false;
		$this->_error = sprintf ($this->T_('WARNING: %s does not support %s.'), get_class($this), $property);
		return true;
	}

	public function move( $direction, $where='' ) {
		$compops = array (-1 => '<', 0 => '=', 1 => '>');
		$relation = $compops[($direction>0)-($direction<0)];
		$ordering = ($relation == '<' ? 'DESC' : 'ASC');
		$k = $this->rowKey;
		$o1 = $this->ordering;
		$k1 = $this->$k;
		$database = $this->getDatabase();
		$sql = "SELECT $k, ordering FROM $this->tableName WHERE ordering $relation $o1";
		$sql .= ($where ? "\n AND $where" : '').' ORDER BY ordering '.$ordering.' LIMIT 1';
		$database->setQuery( $sql );
		if ($database->loadObject($row)) {
			$o2 = $row->ordering;
			$k2 = $row->$k;
			$sql = "UPDATE $this->tableName SET ordering = (ordering=$o1)*$o2 + (ordering=$o2)*$o1 WHERE $k = $k1 OR $k = $k2";
			$database->doSQL($sql);
		}
	}

	// public function updateOrder( $where='', $cfid=null, $order=null ) {
	public function updateOrder ($where='', $sequence='', $orders=array()) {
		if ($this->lacks('ordering')) return false;
		$sql = "SELECT $this->rowKey, ordering FROM $this->tableName"
			.($where ? "\n WHERE $where" : '')
			."\n ORDER BY ordering"
			.($sequence ? ','.$sequence : '');
		$rows = $this->getDatabase()->doSQLget($sql, 'stdClass', $this->rowKey);
		$allrows = array();
		foreach ($rows as $key=>$row) $allrows[(isset($orders[$key]) ? $orders[$key] : $row->ordering)] = $key;
		ksort($allrows);
		$cases = '';
		$order = 10;
		foreach ($allrows as $ordering=>$id) {
			if ($order != $rows[$id]->ordering) $cases .= " WHEN $this->rowKey = $id THEN $order ";
			$order += 10;
		}
		if ($cases) $this->getDatabase()->doSQL("UPDATE $this->tableName SET ordering = CASE ".$cases.' ELSE ordering END');
		return true;
	}

	// Caller needs to find out the number of affected rows, not rely on true or false return
	public function delete( $key=null ) {
		$k = $this->rowKey;
		if ($key) $this->$k = intval( $key );
		$this->getDatabase()->doSQL( "DELETE FROM $this->tableName WHERE $this->rowKey = '".$this->$k."'" );
		return true;
	}

	public function checkout( $who=0, $key=null ) {
		if (!$who) $who = $this->getUser()->id;
		if ($this->lacks('checked_out')) return false;
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		$time = date( "Y-m-d H:i:s" );
		$this->getDatabase()->doSQL( "UPDATE $this->tableName"
		. "\nSET checked_out='$who', checked_out_time='$time'"
		. "\nWHERE $k='".$this->$k."'"
		);
		return true;
	}

	protected function getUser () {
		return aliroUser::getInstance();
	}

	public function checkin( $key=null ) {
		if ($this->lacks('checked_out')) return false;
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		$this->getDatabase()->doSQL( "UPDATE $this->tableName"
		. "\nSET checked_out='0', checked_out_time='0000-00-00 00:00:00'"
		. "\nWHERE $k='".$this->$k."'"
		);
		return true;
	}

	function isCheckedOut ($userid=0) {
		return ($this->checked_out AND $userid != $this->checked_out) ? true : false;
	}

}

// This class provided for backwards compatibility
abstract class mosDBTable extends aliroDBGeneralRow {
	protected $DBclass = 'aliroDatabase';
	public $_tbl = '';
	public $_tbl_key = '';
	protected $tableName = '';
	protected $rowKey = '';

	public function mosDBTable ($table='', $keyname='id') {
		$this->_tbl = $this->tableName = $table;
		$this->_tbl_key = $this->rowKey = $keyname;
	}

	public function __call ($method, $args) {
		if ('mosDBTable' == $method) {
			call_user_func_array(array($this, '__construct'), $args);
		}
		else trigger_error($this->T_('Invalid method call to mosDBTable'));
	}

	// protected function __get
	public function __get ($name) {
		if ($name == '_db') return call_user_func (array($this->DBclass, 'getInstance'));
		else return null;
	}

	function filter( $ignoreList=null ) {
		$request = aliroRequest::getInstance();
		foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) {
			if (!is_array($ignoreList) OR !in_array($k, $ignoreList)) {
				$this->$k = $request->doPurify($this->$k);
			}
		}
	}

	function get( $_property ) {
		return isset($this->$_property) ? $this->$_property :null;
	}

	function set( $_property, $_value ) {
		$this->$_property = $_value;
	}

	function reset ($value=null) {
		foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) $this->$k = $value;
	}

	function hit( $keyvalue=null ) {
		$k = $this->rowKey;
		if (null !== $keyvalue) $this->$k = intval($keyvalue);
		$this->getDatabase()->doSQL( "UPDATE $this->tableName SET hits=(hits+1) WHERE $this->rowKey='{$this->$k}'" );

		if (aliroComponentConfiguration::getInstance('com_content')->enable_log_items) {
			$now = date( "Y-m-d" );
			$this->getDatabase()->doSQL("INSERT INTO #__core_log_items VALUES"
				."\n ('$now','$this->tableName','".$this->$k."','1')"
				."\n ON DUPLICATE KEY UPDATE hits=(hits+1)");
		}
	}

	function save( $source, $order_filter ) {
		if (!$this->bind($source) OR !$this->check() OR !$this->store()OR !$this->checkin()) return false;
		$this->updateOrder( ($order_filter AND !empty($this->$order_filter)) ? "`$order_filter`='{$this->getDatabase()->getEscaped($this->$order_filter)}'" : "" );
		$this->_error = '';
		return true;
	}

	function publish_array( $cid=null, $publish=1, $myid=0 ) {
		if (!is_array( $cid ) OR count( $cid ) < 1) {
			$this->_error = "No items selected.";
			return false;
		}
		foreach ($cid as $i=>$id) $cid[$i] = intval($id);
		$cids = implode( ',', $cid );
		$publish = $publish ? 1 : 0;
		$myid = intval($myid);
		$this->getDatabase()->doSQL( "UPDATE $this->tableName SET published=$publish"
		. "\nWHERE $this->rowKey IN ($cids) AND (checked_out=0 OR checked_out=$myid)"
		);
		return true;
	}

	function publish( $cid=null, $publish=1, $user_id=0 ) {
		$this->publish_array($cid, $publish, $myid);
	}

	function toXML( $mapKeysToText=false ) {
		if ($mapKeysToText) $attrib = ' mapkeystotext="true"';
		$middle = '';
		foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) {
			$v = $this->$k;
			if (is_null($v) OR is_array($v) OR is_object($v) OR (is_string($v) AND '_' == $v[0])) continue;
			$middle .= "<$k><![CDATA[$v]]></$k>";
		}
		return <<<TO_XML
<record table="$this->tableName"$attrib>
$middle
</record>
TO_XML;

	}
}

/**
* Abstract class for classes where the objects of the class can be relatively easily
*  stored in a single database table.  Can usually be adapted to more complex cases.
*  notSQL() must return an array of strings, where each string is the name of a
*  variable that is NOT in the database table, or is not written explicitly,
*  e.g. the auto-increment key.  If this is the ONLY non-SQL field, then the
*  child class need not implement it, as that it is already in the abstract class.
*  Child classes may implement timeStampField, in which case it must return the name
*  of a field that will have a timestamp placed in it whenever the DB is written.
*/

abstract class aliroDatabaseRow extends aliroDBGeneralRow {

	// ? protected function __get ?
	public function __get ($property) {
		$database = $this->getDatabase();
		if ('_db' == $property) return $database;
		$field = $database->getFieldInfo ($this->tableName, $property);
		if (!is_object($field)) trigger_error($this->T_('Database row attempt to obtain invalid property: ').$property);
		else if ('auto_increment' == $field->Extra) return 0;
		return $field ? $field->Default : null;
	}

	/* Provided in case child class does not implement it.  Can force any values */
	/* within some limited range.  In particular, can force bools to be 0 or 1 */
	function forceBools () {
		return;
	}

	/* Provided in case the child class does not provide a method for timeStampField */
	function timeStampField () {
		return '';
	}

	/* Default method for identifying fields not to be written to the DB */
	/* The child classes may override this and return more items in the array */
	function notSQL () {
		return array ($this->rowKey);
	}

	function assignRoles ($roles, $action, $access) {
		$authorisation = aliroAuthorisationAdmin::getInstance();
		$key = $this->rowKey;
		$authorisation->dropPermissions($action, $this->subjectName, $this->$key);
		if (in_array('Visitor', $roles)) return;
		$none = array_search('none', $roles);
		if (false !== $none) unset($roles[$none]);
		$database = $this->getDatabase();
		foreach ($roles as $role) {
			$role = $database->getEscaped($role);
			$authorisation->permit ($role, $access, $action, $this->subjectName, $this->$key);
		}
	}

}
