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
 * aliroEditor is the basic framework for editors.  The actual editors are mambots
 * and the methods provided here trigger the active editor mambot(s).  The class is
 * a singleton but does not have any data suitable to be cached.
 *
 */

final class aliroEditor {

	private static $instance = null;
	private $initiated = false;

	private function __construct () {
		// Just here to enforce singleton
	}

	private function __clone () {
		// Just here to enforce singleton
	}

	public static function getInstance () {
	    return self::$instance instanceof self ? self::$instance : (self::$instance = new self());
	}

	public function initEditor() {
		$this->initiated = true;
		return $this->triggerEditor ('onIniEditor');
	}

	public function getEditorContents( $editorArea, $hiddenField ) {
		echo $this->getEditorContentsText($editorArea, $hiddenField);
	}

	public function getEditorContentsText ( $editorArea, $hiddenField ) {
		if (!$this->initiated) $this->initEditor();
		return $this->triggerEditor ('onGetEditorContents', array($editorArea, $hiddenField));
	}

	public function editorAreaText ($name, $content, $hiddenField, $width, $height, $col, $row) {
		if (!$this->initiated) $this->initEditor();
		return $this->triggerEditor ('onEditorArea', array($name, $content, $hiddenField, $width, $height, $col, $row));
	}
	// just present a textarea
	public function editorArea( $name, $content, $hiddenField, $width, $height, $col, $row ) {
		echo $this->editorAreaText ($name, $content, $hiddenField, $width, $height, $col, $row);
	}

	private function triggerEditor ($trigger, $arguments=null) {
		$mambothandler = aliroMambotHandler::getInstance();
		if ($arguments) $result = $mambothandler->triggerOnce($trigger, $arguments);
		else $result = $mambothandler->triggerOnce($trigger);
		return $result;
	}

}