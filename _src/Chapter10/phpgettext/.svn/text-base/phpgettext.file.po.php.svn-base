<?php
/**
 * @version		0.95
 * @author      Carlos Souza, Martin Brampton
 * @copyright   Copyright (c) 2005 Carlos Souza <csouza@web-sense.net>
 * @copyright	Copyright (c) 2008 Martin Brampton <counterpoint@aliro.org>
 * @package     PHPGettext
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		http://www.aliro.org
 */

class PHPGettextFilePO extends PHPGettextFilePOT {
	protected $mode = _MODE_PO_;
	
	public function save () {
		$this->headers['PO-Revision-Date'] = date('Y-m-d G:iO');
		parent::save();
	}

    public function translate ($translations) {$n = count($this->strings);
		foreach ($this->strings as &$onestring) {
            if (!empty($translations[$onestring->msgid])){
               $onestring->setmsgstr ($translations[$onestring->msgid]);
            }
        }
    }
	
	public function saveAsMO () {
		$mofile = new PHPGettextFileMO(false, $this->name, $this->path, $this->lang);
		$mofile->comments = $this->comments;
		$mofile->headers = $this->headers;
		$mofile->strings = $this->strings;
		$mofile->save();
	}
	
}