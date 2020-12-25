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

class PHPGettextFileGLO extends PHPGettextFilePOT {
	protected $mode = _MODE_PO_;

	public function __construct ($name, $path) {
		parent::__construct($name, $path, 'glossary');
	}

    public function merge (&$glossary) {
        foreach ($this->strings as $msgid => $string) {
              if (!$glossary[$string->msgid]) $glossary[$string->msgid] = $string;
              else {
                  $glossary[$string->msgid]->comments = array_merge($glossary[$string->msgid]->comments,$string->comments);
                  $glossary[$string->msgid]->comments = array_unique($glossary[$string->msgid]->comments);
              }
        }
        $n = count($glossary);
        unset($this->strings);
        foreach ($glossary as $msgid => $string){
           $this->addentry($msgid, $string->msgid_plural, $string->msgstr, $string->comments);
        }
    }
	
}