<?php
/**
* @package Aliro Open Source - copyright (c) 2006 the Aliro Organisation (http://www.aliro.org)
* Aliro was originally based on Mambo (open source) and contains original work by Martin Brampton, Lynne Pope, Nic Steenhout, John Long and others
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
* Mambo was originally developed by Miro (www.miro.com.au) in 2000. Miro assigned the copyright in Mambo to The Mambo Foundation in 2005 to ensure
* that Mambo remained free Open Source software owned and managed by the community.
* Mambo is Free Software
*/

class mod_simplemenu implements ifAliroModule {

	public function activate ($module, &$content, $area, $params) {
		// Use the default menu processing within Aliro
		$content = aliroMenuCreator::getInstance()->createMenu($params);
	}

}