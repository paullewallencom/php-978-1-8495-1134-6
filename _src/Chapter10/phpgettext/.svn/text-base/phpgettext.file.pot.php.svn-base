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

class PHPGettextFilePOT extends PHPGettextFile {
	protected $mode = _MODE_POT_;
	private $msgstr = false;
	private $is_fuzzy = false;
	private $nplural = false;
	private $cbuffer = null;

	protected function load ($required=true) {
        $file = $this->filename();
        if (!is_readable($file)) {
            if ($required) trigger_error("Gettext File POT $file not found", E_USER_WARNING);
            return false;
        }
        // load file
        if (!$data = @file($file)) {
            trigger_error("Gettext File POT $file not found", E_USER_WARNING);
            return false;
        }
        $count      = 0;
        // get all strings
        foreach ($data as $line) {
            // comments
            if (strncmp($line, "#", 1) == 0) {
                if ($count < 1) $this->comments[] = $line;
                else {
                    if (strncmp($line, "#,", 2) == 0 AND strpos($line, '/fuzzy/')) $this->is_fuzzy = true;
                    else $this->cbuffer[] = $line;
                }
            }// msgid
			elseif (preg_match('/^msgid_plural\s*"(.*)"\s*/s', $line, $matches)) {
				$this->nplural = true;
				$strings[$count]['msgid_plural'] = $matches[1];
			}
			elseif (preg_match('/^msgid\s*"(.*)"\s*/s', $line, $matches)) {
				$count++;
				$strings[$count]['comments'] = $this->cbuffer ? $this->cbuffer : '';
				$strings[$count]['msgid']    = '';
				$strings[$count]['msgid_plural']    = '';
				$strings[$count]['msgstr']   = '';
				$strings[$count]['is_fuzzy'] = $this->is_fuzzy;
				if (!empty($matches[1])) $strings[$count]['msgid'] = $matches[1];
				$this->resetTemps();
            } // msgstr
            elseif  (preg_match('/^msgstr\s*"(.*)"\s*|^msgstr\[[0-9]\]\s*"(.*)"\s*/s', $line, $matches)) {
                $msgstr = true;
                if ($this->nplural) $strings[$count]['msgstr'][] = isset($matches[2]) ? $matches[2] : '';
                else $strings[$count]['msgstr'] = $matches[1];
            } // multiline msgid or msgstr
            elseif (preg_match('/^"(.*)"\s*$/s', $line, $matches)) {
                // headers
                if (isset($msgstr) AND 1 == $count) {
                    list($key, $value) = explode(':', $matches[1], 2);
                    $this->headers[$key] = $value;
                }
                elseif (isset($msgstr) && $count > 1) $strings[$count]['msgstr'] .= $matches[1];
				// msgid
                else $strings[$count]['msgid']  .= $matches[1];
            }
        }

        // load the strings
        array_shift($strings);
        for ($a=0; $a < count($strings); $a++) {
            $this->strings[$a] = new PHPGettext_Message($strings[$a]['msgid'], $strings[$a]['msgid_plural']);
            $this->strings[$a]->setmsgstr($strings[$a]['msgstr']);
            $this->strings[$a]->setfuzzy($strings[$a]['is_fuzzy']);
            $this->strings[$a]->setcomments($strings[$a]['comments']);
        }
        return true;
	}
	
	private function resetTemps () {
		$this->msgstr = false;
		$this->is_fuzzy = false;
		$this->nplural = false;
		$this->cbuffer = null;
	}
	
	public function save () {
        $file = $this->filename();
        // open PO file
        if (!is_resource($res = @fopen($file, 'w'))) {
            trigger_error("Cannot create $file.", E_USER_WARNING);
			return false;
        }
        // lock PO file exclusively
        if (!@flock($res, LOCK_EX)) {
            @fclose($res);
            trigger_error("Cannot lock $file.", E_USER_WARNING);
			return false;
        }
        // write comments
		foreach ($this->comments as $line) fwrite($res, trim($line)."\n");
        // write meta info
        if (count($this->headers)) {
            $header = 'msgid ""' . "\nmsgstr " . '""' . "\n";
            foreach ($this->headers as $k => $v) $header .= '"' . $k . ': ' . $v . '\n"' . "\n";
            fwrite($res, $header . "\n");
        }

        // write strings
		foreach ($this->strings as $string) fwrite($res, $string->toString());
        //done
        @fclose($res);
        return true;
	}
	
}
