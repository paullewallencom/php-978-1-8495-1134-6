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

class PHPGettextFileMO extends PHPGettextFile {
	protected $mode = _MODE_MO_;
	
	protected function load () {
        $file = $this->filename();
        if (!file_exists($file)) return false;

        //  read in data file completely
        $f = fopen($file, "rb");
        $data = fread($f, 1<<20);
        fclose($f);

        //  extract header fields and check file magic
        if ($data) {
            $header = substr($data, 0, 20);
            $header = unpack("L1magic/L1version/L1count/L1o_msg/L1o_trn", $header);
            extract($header);
            if ((dechex($magic) == "950412de") && ($version == 0)) {
                //  fetch all strings
                for ($a=0; $a<$count; $a++) {
                    //  msgid
                    $r = unpack("L1len/L1offs", substr($data, $o_msg + $a * 8, 8));
                    $msgid = substr($data, $r["offs"], $r["len"]);
                    unset($msgid_plural);
                    if (strpos($msgid, "\0")) { // plurals
                        list($msgid, $msgid_plural) = explode("\0", $msgid);
                    }
                    //  msgstr
                    $r = unpack("L1len/L1offs", substr($data, $o_trn + $a * 8, 8));
                    $msgstr = substr($data, $r["offs"], $r["len"]);
                    if (isset($msgid_plural)) { // plurals
                        $msgstr = explode("\0", $msgstr);
                    }
                    $strings[$a]['msgid'] = $msgid;
                    $strings[$a]['msgstr'] = $msgstr;
                    $strings[$a]['msgid_plural'] = isset($msgid_plural) ? $msgid_plural : '';
                }
                if (!empty($strings[0]['msgstr'])){ // header
                    $str = explode("\n", $strings[0]['msgstr']);
                    foreach ($str as $s){
                        if (!empty($s)) {
                            @list($key, $value) = explode(':', $s, 2);
                            $this->headers[$key] = $value;
                        }
                    }
                }
                // load the strings
                array_shift($strings);
                for ($a=0; $a < count($strings); $a++) {
                    $this->strings[$a] = new PHPGettext_Message($strings[$a]['msgid'], $strings[$a]['msgid_plural']);
                    $this->strings[$a]->setmsgstr($strings[$a]['msgstr']);
                }
                return true;
            }
        }
        return false;
	}
	
	public function save () {
        $file = $this->filename();

        // open MO file
        if (!is_resource($res = @fopen($file, 'w'))) {
            trigger_error("Cannot create '$file'. ", E_USER_ERROR);
			return false;
        }
        // lock MO file exclusively
        if (!@flock($res, LOCK_EX)) {
            @fclose($res);
            trigger_error("Cannot lock '$file'. ", E_USER_WARNING);
        }

        // get the headers
        $headers = "";
        foreach ($this->headers as $key => $val) {
            $headers .= $key . ': ' . $val . "\n";
        }
        $strings[] = array('msgid' => "", 'msgstr' => $headers);

        // don't write fuzzy entries
        foreach ($this->strings as $message) {
            if (!$message->is_fuzzy) {
                $strings[] = array('msgid' => $message->msgid,'msgid_plural' => $message->msgid_plural, 'msgstr' => $message->msgstr);
            }

        }

        $count = count($strings);
        fwrite($res, pack('L', (int) 0x950412de));  // magic number
        fwrite($res, pack('L', 0));                 // revision  0
        fwrite($res, pack('L', $count));            // N - number of strings
        $offset = 28;
        fwrite($res, pack('L', $offset));           // O - offset of table with original strings
        $offset += ($count * 8);
        fwrite($res, pack('L', $offset));           // T - offset of table with translation strings
        fwrite($res, pack('L', 0));                 // S - size of hashing table (set to 0 to omit the table)
        $offset += ($count * 8);
        fwrite($res, pack('L', $offset));           // H - offset of hashing table

        // offsets for original strings
        for ($a=0; $a<$count; $a++) {
            if (isset($strings[$a]['msgid_plural'])) { // plurals
                $strings[$a]['msgid'] = $strings[$a]['msgid'] ."\0".$strings[$a]['msgid_plural'];
            }
            $len = strlen($strings[$a]['msgid']);
            fwrite($res, pack('L', $len));
            fwrite($res, pack('L', $offset));
            $offset += $len + 1;
        }

        // offsets for translated strings
        for ($a=0; $a<$count; $a++) {
            if (is_array($strings[$a]['msgstr'])) { // plurals
                $strings[$a]['msgstr'] = implode("\0", $strings[$a]['msgstr']);
            }
            $len = strlen($strings[$a]['msgstr']);
            fwrite($res, pack('L', $len));
            fwrite($res, pack('L', $offset));
            $offset += $len + 1;
        }

        // write original strings
        foreach ($strings as $str) {
            fwrite($res, $str['msgid'] . "\0");
        }
        // write translated strings
        foreach ($strings as $str) {
            fwrite($res, $str['msgstr'] . "\0");
        }
        // done
        @flock($res, LOCK_UN);
        @fclose($res);
        return true;
	}
	
}