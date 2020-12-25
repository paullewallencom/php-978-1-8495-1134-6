<?php
/**
 * @version		2.0
 * @author      Carlos Souza
 * @copyright   Copyright (c) 2005 Carlos Souza <csouza@web-sense.net>
 * @copyright	Copyright (c) 2008 Martin Brampton <counterpoint@aliro.org> Migrate to pure PHP5
 * @package     PHPGettext
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		http://phpgettext.web-sense.net
 * @link 		http://www.aliro.org
 *
 *
 */

class PHPGettext_Message {

    public $comments = array();
    public $is_fuzzy = false;
    public $msgid = '';
    public $msgid_plural = '';
    public $msgstr = '';

    // --- OPERATIONS ---

    public function __construct ($msgid="", $msgid_plural=null) {
        $this->msgid = $msgid;
        $this->msgid_plural =  $msgid_plural;
    }

    public function setmsgstr ($msgstr) {
        if (is_array($msgstr) AND count($msgstr) == 1) $this->msgstr = $msgstr[0];
        else $this->msgstr = $msgstr;
    }

    public function setfuzzy ($is_fuzzy = true) {
        $this->is_fuzzy = ($is_fuzzy) ? true : false;
    }

    public function setcomments ($comments) {
        return is_array($comments) ? ($this->comments = $comments) : false;
    }

    public function reset ($property = 'all') {
    	$attributes = array ('comments' => array(), 'is_fuzzy' => false, 'msgid' => '', 'msgstr' => '', 'msgid_plural' => '');
    	if ('all' == $property) foreach ($attributes as $attrib=>$default) $this->$attrib = $default;
    	elseif (isset($attributes[$property])) $this->$property = $attributes[$property];
    }

    public function toString () {
        $string = '';
        // comments
        foreach ($this->comments as $comment) {
            if (!is_null($comment) AND !preg_match('/^#,/', $comment)) $string .= trim($comment)."\n";
            elseif (0 == strncmp($comment, "#:", 2)) $string .= trim($comment)."\n";
        }
        // fuzzy entries
        if ($this->is_fuzzy) $string .= "#, fuzzy\n";

        // There was at one time code here involving wordwrap, but that is an unsafe function with UTF-8
        // The commented out code has therefore been completely deleted (Jan 2008)

        // msgid
        if (strpos($this->msgid, "\n")) {
            $string .= "msgid \"\"\n";
            $msgid = explode("\n", $this->msgid);
            foreach ($msgid as $line) $string .= "\"$line\\n\"\n";
        }
        else $string .= "msgid \"$this->msgid\"\n";

        // msgid_plural
        if (!empty($this->msgid_plural)) $string .= "msgid_plural \"$this->msgid_plural\"\n";

        // msgstr
        if (is_array($this->msgstr)) {
            foreach ($this->msgstr as $k => $msgstr) $string .= "msgstr[$k] \"$msgstr\"\n";
            $string .= "\n";
        }
        elseif (strpos($this->msgstr, "\n")) {
            $string .= "msgstr \"\"\n";
            $msgstr = explode("\n", $this->msgstr);
            foreach ($msgstr as $line) $string .= "\"$line\\n\"\n";
            $string .= "\n";
        }
        else $string .= "msgstr \"$this->msgstr\"\n\n";

        return $string;
    }

} /* end of class PHPGettext_Message */