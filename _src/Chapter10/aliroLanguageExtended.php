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
 * The aliroLanguageExtended class includes methods that are used only for the
 * more complex manipulations of language information, and is therefore split off
 * the aliroLanguageBasic class for efficiency reasons.
 *
 */

final class aliroLanguageExtended extends aliroLanguageBasic {
	
	public function __construct ($lang=null, $path=null, $load_catalogs=false) {
		if ($lang) parent::__construct ($lang, $path, $load_catalogs);
		else {
			$this->path = $path ? $path : _ALIRO_CLASS_BASE.'/language/';
			$this->isValid = true;
		}
	}

	public function update () {
		$vars = array_keys(get_object_vars($this));
        foreach ($_POST as $k => $v) {
            if (in_array($k, $vars)) {
                $this->$k = $v;
            }
        }
        $this->setPlurals($_POST['plural_form']);
        $this->save();
	}

	public function export () {
		$zipbase = $this->class_base;
        chdir($zipbase);
        $filename = "AliroLanguage_{$this->name}.zip";
        $zipfile = $zipbase.'/tmp/'.$filename;
        //$archive = new PclZip($zipfile);
		$archive = new zipfile();
        foreach ($this->files as $file) {
            //$v_list = $archive->add($zipbase.'/language/'.$this->lang.'/'.$file['filename'], PCLZIP_OPT_REMOVE_PATH, $zipbase.'/language/'.$this->lang);
			$archive->addfile(file_get_contents($zipbase.'/language/'.$this->name.'/'.$file['filename']), $file['filename']);
            //if (0 == $v_list) die("Error : ".$archive->errorInfo(true));
        }
		$archive->output($zipfile);
        @ob_end_clean();
        ob_start();
        header('Content-Type: application/x-zip');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        if (isset($_SERVER['HTTP_USER_AGENT']) AND false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
        }
        readfile($zipfile);
        ob_end_flush();
        aliroFileManager::getInstance()->deleteFile($zipfile);
        exit(0);
	}

    public function createLanguage ($iso639, $iso3166, $iso3166_3) {
        $lang  = $iso639;
        $lang .= strlen($iso3166) == 2 ? '-'.$iso3166 : '';
        $locales = $this->getLocales();
        $default = $this->getLocale($iso639);
        $lvars = array_keys(get_class_vars('aliroLanguageBasic'));
        foreach ($default as $k => $v) if (in_array($k, $lvars)) $this->$k = $v;
        foreach ($_POST as $k => $v) if (in_array($k, $lvars)) $this->$k = $v;
        $this->name = $lang;
        $this->description = $this->title.' Locale';
        if (!empty($this->territory)) $this->description .= ' For '.$this->territory;
        $this->locale = $lang.'.'.$this->charset.','.$lang.','.$iso639.','.strtolower($this->title);
        $this->iso3166_3 = $iso3166_3;
        $this->creationdate = date('d-m-Y');
        $this->author = 'Aliro Software';
        $this->authorurl = 'http://www.aliro.org';
        $this->authoremail = 'translation@aliro.org';
        $this->copyright = 'Refer to root index.php';
        $this->license = 'http://www.gnu.org/copyleft/gpl.html GNU/GPL';
        $this->setPlurals($_POST['plural_form']);
		if (!$this->canCreateLanguage()) {
			aliroRequest::getInstance()->setErrorMessage (T_('Cannot create language, it already exists'), _ALIRO_ERROR_FATAL);
			die('Cannot create language, already exists');
			return;
		}			

        // $textdomain = rtrim($language->path, '\/');
        $dir = $this->path.$this->name;
        $untranslated = $this->path.'untranslated';
        $charset = $this->charset;
		$untransdir = new aliroDirectory($untranslated);
		$langfiles = $untransdir->listFiles('\.pot$', 'file');
        // $langfiles  = mosReadDirectory($untranslated,'.pot$');
        $fmanager = aliroFileManager::getInstance();
        $fmanager->createDirectory($dir.'/LC_MESSAGES');
        // @mkdir($dir);
        // @mkdir($dir.'/LC_MESSAGES');

        //$gettext_admin = new PHPGettextAdmin();
        foreach ($langfiles as $domain)  {
            $domain = substr($domain,0,-4);
            /*if (file_exists("$textdomain/glossary/$lang.$charset.po")) {
                copy("$textdomain/glossary/$lang.$charset.po", "$dir/$lang.po");
                $gettext_admin->initialize_translation($domain, $textdomain, $lang, $charset);
                $gettext_admin->compile($lang, $textdomain, $charset);
            } else {*/
                $fmanager->forceCopy("$untranslated/$domain.pot", "$dir/$domain.po");
            //}
        }
        //if (!file_exists("$textdomain/$lang/$lang.po")) {
        //    @copy("$textdomain/glossary/untranslated.pot", "$textdomain/$lang/$lang.po");
        //}
        $this->save();
    }
	
	private function canCreateLanguage () {
		if ('en' == $this->name OR aliroExtensionHandler::getInstance()->getExtensionByName($this->name)) {
			return false;
		}
		$extension = new aliroExtension();
		$extension->name = $this->title;
		$extension->formalname = $this->name;
		$extension->type = 'language';
		$extension->description = $this->description;
		$extension->timestamp = date('Y-m-d');
		$extension->store();
		aliroExtensionHandler::getInstance()->clearCache();
		return true;
	}

    public function save() {
		$mapcharset = charsetmapping::getInstance()->map();
		$request = aliroRequest::getInstance();
        $task = $request->getParam($_REQUEST, 'task');
        $this->updateFiles();
        $xml = $this->toXML();
        if (("addpage" == $request->getParam($_POST, 'page_') AND $task=="save") OR $task=="convert") {
            if (false AND strtolower($this->charset) != 'utf-8') {
    			$xml = $this->iconvert("utf-8", $mapcharset[$this->charset], $xml);
            }
        }
		$name = $this->getFileName();
        file_put_contents($this->path.$name.'/'.$name.'.xml', $xml);
    }

    public function setPlurals($exp) {
        preg_match('/nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*(.*)\s*;/', $exp, $plurals);
        $this->plural_form = array('nplurals' => $plurals[1], 'plural' => $plurals[2], 'expression' => $plurals[0]);
    }
	
    protected function updateFiles() {
        $dir = $this->path . $this->name . '/';
		$langdir = new aliroDirectory($dir);
		$pofiles = $langdir->listFiles('\.po$', 'file');
        set_time_limit(60);
        foreach ($pofiles as $lf) {
            $domain = substr($lf, 0, -3);
            $catalog = new PHPGettextFilePO (true, $domain, $this->path, $this->name);
            $file['filename'] = $lf;
            $file['domain'] = $domain;
            $file['strings'] = count($catalog->strings);
            $file['percent'] = '';
            $file['translated'] = 0;
            $file['fuzzy'] = 0;
            $file['filetype'] = 'po';
            $pluralfuzz = false;
            foreach ($catalog->strings as $msg) {
                if (is_array($msg->msgstr)) {
                    foreach ($msg->msgstr as $i) $unt = empty($i);
                    if (!$unt) $file['translated']++;
                }
                if (!is_array($msg->msgstr) AND !empty($msg->msgstr) AND !$msg->is_fuzzy) $file['translated']++;
                if ($msg->is_fuzzy) $file['fuzzy']++;
            }
            $nonfuzzy = $file['strings'] - $file['fuzzy'];
            $nonfuzzy = max(1, $nonfuzzy);
            $file['percent'] = round($file['translated'] * 100 / $nonfuzzy, 2);
            unset($nonfuzzy);
            $this->files[] = $file;
        }
        $this->files[] = array('filename'=>"$this->name.xml",'domain'=>"",'strings'=>"",'percent'=>"",'translated'=>0,'fuzzy'=>0,'filetype'=>'xml');
		$langdir = new aliroDirectory($dir.'LC_MESSAGES/');
		$mofiles = $langdir->listFiles('\.mo$', 'file');
        set_time_limit(60);
        foreach ($mofiles as $lf) {
            $this->files[] = array('filename'=>"LC_MESSAGES/$lf",'domain'=>"",'strings'=>"",'percent'=>"",'translated'=>0,'fuzzy'=>0,'filetype'=>'mo');
        }
        if (file_exists($this->path."/glossary/{$this->name}.{$this->charset}.po")) {
        	$this->files[] = array('filename'=>"glossary/{$this->name}.{$this->charset}.po",'domain'=>"",'strings'=>"",'percent'=>"",'translated'=>0,'fuzzy'=>0,'filetype'=>'gl');
        }
    }
	
	protected function toXML() {
        $array[] = array('tag' => 'extinstall', 'type' => 'open', 'level' => 1, 'attributes' => array('version' => '2.0', 'type' => 'language'));
        $array[] = array('tag' => 'name', 'type' => 'complete', 'level' => 2, 'value' => $this->title);
        $array[] = array('tag' => 'formalname', 'type' => 'complete', 'level' => 2, 'value' => $this->name);
        $array[] = array('tag' => 'version', 'type' => 'complete', 'level' => 2, 'value' => $this->version);
        $array[] = array('tag' => 'description', 'type' => 'complete', 'level' => 2, 'value' => $this->description);
        $array[] = array('tag' => 'creationdate', 'type' => 'complete', 'level' => 2, 'value' => $this->creationdate);
        $array[] = array('tag' => 'author', 'type' => 'complete', 'level' => 2, 'value' => $this->author);
        $array[] = array('tag' => 'authorurl', 'type' => 'complete', 'level' => 2, 'value' => $this->authorurl);
        $array[] = array('tag' => 'authoremail', 'type' => 'complete', 'level' => 2, 'value' => $this->authoremail);
        $array[] = array('tag' => 'copyright', 'type' => 'complete', 'level' => 2, 'value' => $this->copyright);
        $array[] = array('tag' => 'license', 'type' => 'complete', 'level' => 2, 'value' => $this->license);
        $array[] = array('tag' => 'files', 'type' => 'open', 'level' => 2);
        foreach ($this->files as $file) {
            $array[] = array('tag' => 'filename', 'type' => 'complete', 'level' => 3, 'value' => $file['filename'], 'attributes' => array('domain' => $file['domain'] , 'strings' => $file['strings'] , 'translated' => $file['translated'] , 'fuzzy' => $file['fuzzy'] , 'percent' => $file['percent'], 'filetype' => $file['filetype']));
        }
        $array[] = array('tag' => 'files', 'type' => 'close', 'level' => 2);
        $array[] = array('tag' => 'params', 'type' => 'open', 'level' => 2);
        $array[] = array('tag' => 'param', 'type' => 'complete', 'level' => 3, 'attributes' => array('name' => 'locale', 'type' => 'text', 'default' => $this->locale, 'label' => 'Locale String', 'description' => 'Locale string for setlocale() (eg. en, english)'));
        $array[] = array('tag' => 'param', 'type' => 'complete', 'level' => 3, 'attributes' => array('name' => 'charset', 'type' => 'text', 'default' => $this->charset, 'label' => 'Character Set', 'description' => 'Character set for this language.'));
        $array[] = array('tag' => 'param', 'type' => 'complete', 'level' => 3, 'attributes' => array('name' => 'text_direction', 'type' => 'text', 'default' => $this->text_direction, 'label' => 'Text Direction', 'description' => 'left-to-right or light-to-left'));
        $array[] = array('tag' => 'param', 'type' => 'complete', 'level' => 3, 'attributes' => array('name' => 'date_format', 'type' => 'text', 'default' => $this->date_format, 'label' => 'Date Format', 'description' => 'Date format for strftime() (eg. %A, %d %B %Y)'));
        $array[] = array('tag' => 'param', 'type' => 'complete', 'level' => 3, 'attributes' => array('name' => 'plural_form', 'type' => 'text', 'default' => htmlentities($this->plural_form['expression']), 'label' => 'Plural Forms', 'description' => 'Plural Forms expression'));
        $array[] = array('tag' => 'params', 'type' => 'close', 'level' => 2);
        $array[] = array('tag' => 'locale', 'type' => 'open', 'level' => 2, 'attributes' => array('name' => $this->name, 'title' => $this->title, 'territory' => $this->territory, 'locale' => $this->locale, 'text_direction' => $this->text_direction, 'iso639' => $this->iso639, 'iso3166_2' => $this->iso3166_2, 'iso3166_3' => $this->iso3166_3, 'charset' => $this->charset));
        $array[] = array('tag' => 'plural_form', 'type' => 'complete', 'level' => 3, 'attributes' => array('nplurals' => $this->plural_form['nplurals'] , 'plural' => htmlentities($this->plural_form['plural']), 'expression' => htmlentities($this->plural_form['expression'])));
        $array[] = array('tag' => 'date_format', 'type' => 'complete', 'level' => 3, 'value' => $this->date_format);
        $array[] = array('tag' => 'codesets', 'type' => 'open', 'level' => 3);
        foreach ($this->codesets as $charset) $array[] = array('tag' => 'charset', 'type' => 'complete', 'level' => 4, 'value' => $charset);
        $array[] = array('tag' => 'codesets', 'type' => 'close', 'level' => 3);
        foreach ($this->days as $name => $day) $days[$name] = $day;
        $array[] = array('tag' => 'days', 'type' => 'complete', 'level' => 3, 'attributes' => $days);
        foreach ($this->months as $name => $month) $months[$name] = $month;
        $array[] = array('tag' => 'months', 'type' => 'complete', 'level' => 3, 'attributes' => $months);
        $array[] = array('tag' => 'locale', 'type' => 'close', 'level' => 2);
        $array[] = array('tag' => 'extinstall', 'type' => 'close', 'level' => 1);

        $xml = "<?xml version=\"1.0\" encoding=\"$this->charset\"?>\n";
        if ((!empty($array)) AND (is_array($array))) {
            foreach ($array as $key => $value) {
                switch ($value["type"]) {
                    case "open":
                    $xml .= str_repeat("\t", $value["level"] - 1);
                    $xml .= "<" . $value["tag"];
                    if (isset($value["attributes"])) {
                        foreach ($value["attributes"] as $k => $v) {
                            $xml .= sprintf(' %s="%s"', $k, $v);
                        }
                    }
                    $xml .= ">\n";
                    break;
                    case "complete":
                    $xml .= str_repeat("\t", $value["level"] - 1);
                    $xml .= "<" . $value["tag"];
                    if (isset($value["attributes"])) {
                        foreach ($value["attributes"] as $k => $v) {
                            $xml .= sprintf(' %s="%s"', $k, $v);
                        }
                    }
                    $xml .= ">";
                    $xml .= isset($value['value']) ? $value['value'] : false;
                    $xml .= "</" . $value["tag"] . ">\n";
                    break;
                    case "close":
                    $xml .= str_repeat("\t", $value["level"] - 1);
                    $xml .= "</" . $value["tag"] . ">\n";
                    break;
                    default:
                    break;
                }
            }
        }
        return $xml;
    }
	
	private function getLocale ($iso639) {
		try {
			$handler = aliroLanguageExtended::loadLocales();
			foreach ($handler->getXML('locale') as $locale) if ($iso639 == (string) $locale['iso639']) {
				return aliroLanguageExtended::extractOneLocale($locale);
			}
		}
		catch (aliroXMLException $exception) {
			echo $exception->getMessage();
			die('xml error');
		}
	}
	
	private static function extractOneLocale ($locale) {
		$defaults = aliroXML::attribArray($locale);
		$defaults['days'] = aliroXML::attribArray($locale->days);
		$defaults['months'] = aliroXML::attribArray($locale->months);
		$defaults['plural_form'] = aliroXML::attribArray($locale->plural_form);
		$defaults['codesets'] = aliroXML::elementArray($locale->codesets->charset);
		return $defaults;
	}
	
	private static function loadLocales () {
		$xmlhandler = new aliroXML();
		$file = _ALIRO_CLASS_BASE.'/language/locales.xml';
		$xmlhandler->loadFile($file);
		return $xmlhandler;
	}

    // Helper classes that can be called statically

    public function getLanguages() {
    	$path = _ALIRO_ABSOLUTE_PATH.'/language/';
		$alldir = new aliroDirectory($path);
        $langs = array();
		foreach ($alldir->listAll('dir', false, true) as $dir) {
			$langdir = new aliroDirectory($dir);
			foreach ($langdir->listFiles ('.xml$') as $xml) {
				if (substr($xml, 0, -4) != 'locales') {
					$lobj = new aliroLanguageBasic(substr($xml, 0, -4), $path);
					$langs[$lobj->name] = $lobj;
				}
			}
		}
		return $langs;
	}

	public function getLocales () {
		//return aliroLanguageExtended::oldGetLocales();
		$cache = new aliroCache('aliroLanguage');
		$result = $cache->get('getLocales');
		if (is_array($result) AND sha1_file(_ALIRO_ABSOLUTE_PATH.'/language/locales.xml') == $result['sha1_file']) return $result;		
		$result = array('sha1_file' => sha1_file(_ALIRO_ABSOLUTE_PATH.'/language/locales.xml'));
		try {
			$handler = aliroLanguageExtended::loadLocales();
			foreach ($handler->getXML('locale') as $locale) {
				$iso639 = (string) $locale['iso639'];
				$locarray = aliroLanguageExtended::extractOneLocale($locale);
				//$expression = $locarray['plural_form']['expression'];
				//$locarray['plural_form']['expression'] = htmlspecialchars_decode($expression);
				$result['locales'][$iso639] = $locarray;
				$result['languages'][$iso639] = $locarray['title'];
				$territories = array();
				foreach ($locale->territories->territory as $territory) {
					$attribs = aliroXML::attribArray($territory);
					$attribs['territory'] = (string) $territory;
					$territories[] = $attribs;
				}
				$result['territories'][$iso639] = $territories;
				$result['codesets'][$iso639] = $locarray['codesets'];
				$result['directions'][$iso639] = $locarray['text_direction'];
				$result['plural_forms'][$iso639] = $locarray['plural_form']['expression'];
				$result['dateformats'][$iso639] = (string)$locale->date_format;
			}
		}
		catch (aliroXMLException $exception) {
			echo $exception->getMessage();
			die('locales.xml error');
		}
		$cache->save($result);
		return $result;
	}

    public function renderNewlanguage ($renderer) {
        $locales = $this->getlocales();
        $renderer->addvar('language', $this);
        $renderer->addvar('locales',        $locales['locales']);
        $renderer->addvar('territories',    $locales['territories'] );
        $renderer->addvar('codesets',       $locales['codesets']);
        $renderer->addvar('dateformats',    $locales['dateformats']);
        $renderer->addvar('directions',     $locales['directions']);
        $renderer->addvar('plural_forms',   $locales['plural_forms']);
    }
	
	public static function oldGetLocales() {
        $xmlfile = _ALIRO_ABSOLUTE_PATH.'/language/locales.xml';
        $p = xml_parser_create();
        xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($p, implode("", file($xmlfile)), $values);
        xml_parser_free($p);
        $locales = array();
        foreach($values as $key => $value) {
            switch ($value['tag']) {
                case 'locale':
                if ($value['type'] == 'open') {
                    $iso639 = $value['attributes']['iso639'];
                    $language[$iso639] = $value['attributes']['title'];
                    $locale[$iso639] = $value['attributes'];
                    $directions[$iso639] = $value['attributes']['text_direction'];
                }
                break;
                case 'territory':
                $t['iso3166_2'] = $value['attributes']['iso3166_2'];
                $t['iso3166_3'] = $value['attributes']['iso3166_3'];
                $t['territory'] = $value['value'];
                $territories[$iso639][] = $t;
                break;
                case 'charset':
                $locale[$iso639]['codesets'][] = $codesets[$iso639][] = $value['value'];
                break;
                case 'date_format':
                $locale[$iso639]['dateformats'] = $dateformats[$iso639] = $value['value'];
                break;
                case 'days':
                $locale[$iso639]['days'] = $value['attributes'];
                break;
                case 'months':
                $locale[$iso639]['months'] = $value['attributes'];
                break;
                case 'plural_form':
                $exp = '';
                if (!empty($value['attributes']['expression'])) {
                    $locale[$iso639]['plural_form'] = $value['attributes'];
                    $plural_forms[$iso639] = $value['attributes']['expression'];
                }
                break;
            }
        }
        $locales['locales'] = $locale;
        $locales['languages'] = $language;
        $locales['territories'] = $territories;
        $locales['codesets'] = $codesets;
        $locales['dateformats'] = $dateformats;
        $locales['directions'] = $directions;
        $locales['plural_forms'] = $plural_forms;
        return $locales;
    }


}