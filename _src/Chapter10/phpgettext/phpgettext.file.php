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

define('_MODE_MO_', 'mo');
define('_MODE_PO_', 'po');
define('_MODE_POT_','pot');
define('_MODE_GLO_','go');

abstract class PHPGettextFile {

	protected $category = 'LC_MESSAGES';
	public $name = '';
	public $path = '';
	public $lang = 'en';
	public $comments = array();
	public $headers = array();
	public $strings = array();
	
	public function __construct ($load, $name, $path, $lang) {
        $this->name = trim($name);
        $this->path = trim($path);
		$this->lang = trim($lang);
		if ($load) $this->load();
    }
	
	abstract protected function load ();
	
	abstract public function save ();

    protected function filename () {
        $filepath = $this->path.'/';
        $filepath .= !empty($this->lang) ? $this->lang.'/' : '';
        $filepath .= (!empty($this->category) AND _MODE_MO_ == $this->mode) ? $this->category.'/' : '';
        return $filepath.$this->name.'.'.$this->mode;
    }

    public function setComments ($comments) {
        if (is_string($comments)) foreach (explode("\n", trim($comments)) as $comment) {
           	if ($comment AND '#' == $comment[0]) $this->comments[] = $comment."\n";
        }
    }

    public function setHeaders ($headers) {
        if (is_array($headers)) foreach ($headers as $key=>$value) {
            $this->headers[$key] = $value;
        }
    }
	
    public function addentry ($msgid, $msgid_plural=null, $msgstr=null, $comments=array()) {
        $entry =  new PHPGettext_Message($msgid, $msgid_plural);
        if (!is_null($msgstr)) $entry->setmsgstr($msgstr);
        $entry->setcomments($comments);
        $this->strings[$msgid] = $entry;
    }
	
	public function setDefaultCommentsHeaders ($charset='utf-8', $plurals='nplurals=2; plural=n == 1 ? 0 : 1;') {
		$year = date('Y');
        $comments = <<<EOT
# Aliro.
# Copyright (C) 2005 - $year Aliro Software Ltd.
# This file is distributed under the same license as the Aliro package.
# Translation Team <translation@aliro.org>, $year#
#
#, fuzzy
EOT;

		$this->setComments($comments);
		
        $headers = array(
        'Project-Id-Version'    => 'Aliro 2.x',
        'Report-Msgid-Bugs-To'  => 'translation@aliro.org',
        'POT-Creation-Date'     => date('Y-m-d h:iO'),
        'PO-Revision-Date'      => date('Y-m-d h:iO'),
        'Last-Translator'       => 'Translation <translation@aliro.org>',
        'Language-Team'         => 'Translation <translation@aliro.org>',
        'MIME-Version'          => '1.0',
        'Content-Type'          => 'text/plain; charset='.$charset,
        'Content-Transfer-Encoding' => '8bit',
        'Plural-Forms'              => $plurals
        );
		$this->setHeaders($headers);
	}
	
}