<?php
/**
 * @version		0.9
 * @author      Carlos Souza
 * @copyright   Copyright (c) 2005 Carlos Souza <csouza@web-sense.net>
 * @package     PHPGettext
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		http://phpgettext.web-sense.net
 *
 *
 */

class PHPGettextAdmin {
    private $has_gettext = false;
    private $debug = false;
    private $is_windows = false;

    public function __construct ($debug = false) {
        if (!ini_get('open_basedir') AND !ini_get('safe_mode') AND !strstr(ini_get("disable_functions"),"exec") AND @shell_exec('xgettext --help')) {
            $this->has_gettext = true;
        }
        if (substr(strtoupper(PHP_OS), 0, 3) == 'WIN') $this->is_windows = true;
        if ($debug) $this->debug = $debug;
    }
    
    public function has_gettext () {
    	return $this->has_gettext;
    }

	// Mambo uses this in the "convert" action - but there appears to be no route to that
    public function message_format ($domain, $textdomain, $lang, $enc='utf-8') {
        $path   = "$textdomain/$lang";
        if ($this->has_gettext) {
            $cmd = $this->escCommand("msgfmt")." -o ".$this->escPath("{$path}/LC_MESSAGES/{$domain}.mo")." ".$this->escPath("{$path}/{$domain}.po");
            return $this->execute($cmd);
        }
        $catalog = new PHPGettextFilePO (true, $domain, $textdomain, $lang);
        return $catalog->saveAsMO();
    }

	public function compile($lang, $textdomain, $enc='utf-8') {
		aliroFileManager::getInstance()->createDirectory("$textdomain/$lang/LC_MESSAGES/");
		$catalog = new PHPGettextFilePO (false, $lang, $textdomain, $lang);
		$catalog->setDefaultCommentsHeaders();
		$d = new aliroDirectory($textdomain."/".$lang);
		foreach ($d->listFiles('.po$') as $filename) {
			$catalog_aux = new PHPGettextFilePO (true, basename($filename, '.po'), $textdomain, $lang);
			foreach ($catalog_aux->strings as $msgid => $string){
				if (!$string->is_fuzzy AND $string->msgstr AND !(is_array($string->msgstr) AND in_array('',$string->msgstr))) {
					$catalog->addentry($string->msgid, $string->msgid_plural,$string->msgstr, $string->comments );
				}
			}
		}
        return $catalog->saveAsMO();
    }

    public function initialize_translation ($domain, $textdomain, $lang, $enc='utf-8') {
        if (!$this->has_gettext) return false;
        set_time_limit(120);
        $path   = "$textdomain/$lang";
        copy("$textdomain/untranslated/$domain.pot", "$path/ref.po");
        $cmd = $this->escCommand("msgmerge")." --width=80 --compendium ".$this->escPath("{$textdomain}/glossary/{$lang}.{$enc}.po")." -o ".$this->escPath("{$path}/{$domain}.po")." ".$this->escPath("{$path}/ref.po")." ".$this->escPath("{$textdomain}/untranslated/{$domain}.pot");
        $this->execute($cmd);
        unlink("$textdomain/$lang/ref.po");
    }

    public function update_translation($domain, $textdomain, $lang, $enc='utf-8') {
        if (!file_exists("$textdomain/glossary/$lang.$enc.po")) return false;
        $catalog_aux = new PHPGettextFileGLO ($lang.".".$enc, $textdomain);
        $catalog_aux->load();
        foreach ($catalog_aux->strings as $msgid => $string){
           if (!$string->is_fuzzy) $trans[$string->msgid] = $string->msgstr;
        }
        $catalog = new PHPGettextFilePO (true, $domain, $textdomain, $lang);
		$charsets = explode("=",$catalog->headers["Content-Type"]);
		$codecharset = str_replace("\\n","",strtolower($charsets[1]));
		$NewEncoding = new ConvertCharset();
		$mapcharset = charsetmapping::getInstance()->map();
		foreach ($trans as $key => $tran) if ('utf-8' != trim($mapcharset[$codecharset])) {
			$trans[$key] = $NewEncoding->Convert($tran,"utf-8",trim($mapcharset[$codecharset]),false);
        }
        $catalog->translate($trans );
        return $catalog->save();
    }

    public function add_to_dict($domain, $textdomain, $lang, $enc='utf-8') {
        $textdomain = rtrim($textdomain, '\/');
        $path = "$textdomain/$lang";
        aliroFileManager::getInstance()->createDirectory("$textdomain/glossary/");
        $catalog = new PHPGettextFilePO(true, $domain, $textdomain, $lang);
        foreach ($catalog->strings as $msgid => $string){
			if (!$string->is_fuzzy AND $string->msgstr AND !(is_array($string->msgstr) AND in_array('',$string->msgstr))) {
				$new[$string->msgid] = $string;
			}
		}
        $glossary = new PHPGettextFileGLO ($lang.".".$enc, $textdomain);
        if (!$glossary->load(false)) {
            $glossary->setDefaultCommentsHeaders();
            $glossary->save();
        }
        $glossary->merge($new );
        $glossary->save();
        $language = new aliroLanguageExtended($lang);
        $language->save();
        return true;
	}

	// Mambo uses this in the "convert" action - but there is no code to load action.convert.php
    public function convert_charset($domain, $textdomain, $lang, $from_charset, $to_charset) {
        $path = "$textdomain/$lang";
        if ($this->has_gettext) {
            $cmd = $this->escCommand("msgconv")." --to-code=$to_charset -o ".$this->escPath("{$path}/{$domain}.po")." ".$this->escPath("{$path}/{$domain}.po");
            $ret = $this->execute($cmd);
            return $ret;
        }
        if (!class_exists('ConvertCharset')) {
            return false;
        }
        $catalog = new PHPGettextFilePO (true, $domain, $textdomain, $lang);
        $catalog->headers['Content-Type'] = "text/plain; charset=$to_charset\n";
        $NewEncoding = new ConvertCharset();
		foreach ($catalog->strings as $index => $message) if (empty($message->msgid_plural)) {
			$catalog->strings[$index]->msgstr = $NewEncoding->Convert($message->msgstr,$from_charset,$to_charset,false);
        }
        return $catalog->save();
    }
    
    // Convert commonly used language definitions to use T_()
    public function convertDefines ($defines) {
    	$regex = '/(define[^,]*\,)([^;]*;)/i';
    	$lines = explode("\n", $defines);
    	$converted = '';
    	foreach ($lines as $line) {
			$cline = preg_replace($regex, '$1T_($2', $line);
			$cline = str_replace(');', '));', $cline);
			$converted .= $cline."\n";
    	}
		return $converted;
    }
    
    public function xgettext($domain, $textdomain, $php_sources, $lang='untranslated') {
        if ($n = count($php_sources)) {
			$cmd  = $this->escCommand('xgettext');
			if (file_exists("$textdomain/$lang/$domain.pot")) $cmd  .= ' -j ';
			$cmd  .= " -n -c --sort-by-file --keyword=T_ --keyword=Tn_:1,2 --keyword=Td_:2 --keyword=Tdn_:2,3 --output-dir=".$this->escPath("{$textdomain}/{$lang}")." -o {$domain}.pot";
			if ($n > 10) {
				$tmp_name = substr(uniqid(),0,8).'.txt';
				$tmpfile = _ALIRO_SITE_BASE."/tmp/$tmp_name";
				$fp = file_put_contents($tmpfile, implode("\r\n", $php_sources), LOCK_EX);
				$cmd = $cmd." --files-from=".$this->escPath($tmpfile);
			} 
			else {
				foreach ($php_sources as $psource) $psource = $this->escPath($psource);
				$cmd = $cmd.' '.implode(' ', $php_sources);
			}
			$ret = $this->execute($cmd);
			if ($n > 10) @unlink($tmpfile);
			return $ret;
		}
		return false;
    }

    private function execute($cmd) {
        if ($this->has_gettext) {
			$lastline = exec($cmd, $output, $retval);
			if ($retval) trigger_error(T_('PHPGettextAdmin execute failure'), E_USER_ERROR);
			if ($this->debug) trigger_error(T_('Debug PHPGettextAdmin'));
			return $retval;
		}
		else return false;
    }

    private function escCommand($command){
        if ($this->is_windows) $command = "call \"{$command}\"";
        return $command;
    }

    private function escPath($path){
        if ($this->is_windows) $path = "\"".str_replace("/","\\",$path)."\"";
        return $path;
    }

}