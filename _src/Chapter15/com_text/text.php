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

// This is the manager for the component
// The URI for this component will always include option=com_text
// There can also be a control variable called task
// The default value for task is set to be display
// The title bar of the browser will be set with Information
// Alternatives can be used to specify that other values for the control
//	variable will be processed as if they were something else.
class textUser extends aliroComponentUserManager {

	public function __construct ($component, $system, $version, $menu) {
		// For example, could be $alternatives = array ('show' => 'display');
		// This would cause a task value of "show" to trigger the method
		//	that is used to handle "display".
		$alternatives = array ();
		parent::__construct ($component, 'task', $alternatives, 'display', T_('Information'), $system, $version, $menu);
	}

}

// This is the common base class for controller classes for this component
abstract class textUserControllers extends aliroComponentControllers {
	protected $manager = '';

	// This is not required unless extra code is to be included in the constructor
	protected function __construct ($manager) {
		parent::__construct($manager);
	}

	// Additional methods can be added here, common across individual task controllers

}