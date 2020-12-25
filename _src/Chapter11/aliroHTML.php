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
 * aliroHTML is progressively taking over from mosHTML.  It is a singleton rather
 * than a set of static methods, for both style and efficiency reasons.  The
 * mosHTML interface still exists, but makes calls to aliroHTML.
 *
 */

class aliroHTML {
	private static $instance = __CLASS__;
	private $toggleManyDone = false;

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self::$instance());
	}

	public function makeOption ($value, $text='', $selected=false, $valuename='value', $textname='text') {
		$obj = new stdClass;
		$obj->$valuename = $value;
		$obj->$textname = trim($text) ? $text : $value;
		$obj->selected = $selected;
		return $obj;
	}

	// Takes an array of objects and uses it to create a select list
	public function selectList ($selections, $tag_name, $tag_attribs='', $key='value', $text='text', $selected=NULL ) {
		if (!is_array($selections)) return '';
		$key = empty($key) ? 'value' : $key;
		$text = empty($text) ? 'text' : $text;
		$selectproperties = array();
		if (is_array($selected)) foreach ($selected as $select) {
			if (is_object($select)) $selectproperties[] = $select->$key;
			else $selectproperties[] = $select;
		}
		else $selectproperties = array($selected);
		$selecthtml = '';
		foreach ($selections as $selection) {
			$select = (!empty($selection->selected) OR in_array($selection->$key, $selectproperties, true)) ? 'selected="selected"' : '';
			$selecthtml .= <<<AN_OPTION
			<option value="{$selection->$key}" $select>
				{$selection->$text}
			</option>
AN_OPTION;
		}
		return <<<THE_SELECT
		<select name="$tag_name" id="$tag_name" $tag_attribs>
			$selecthtml
		</select>
THE_SELECT;
	}

	public function radioList ($arr, $tag_name, $tag_attribs, $selected=null, $key='value', $text='text') {
		$html = '';
		foreach ($arr as $choice) {
			$extra = @$choice->id ? " id=\"$choice->id\"" : '';
			if (is_array($selected)) foreach ($selected as $obj) {
				if ($choice->$key == $obj->$key) {
					$extra .= ' selected="selected"';
					break;
				}
			}
			else $extra .= ($choice->$key == $selected ? " checked=\"checked\"" : '');
			$html .= <<<RADIO
			<input type="radio" name="$tag_name" value="{$choice->$key}" $extra $tag_attribs />
			{$choice->$text}
RADIO;
		}
		return $html;
	}

	public function yesnoRadioList ($tag_name, $tag_attribs, $selected, $yes=_CMN_YES, $no=_CMN_NO ) {
		$arr = array(
		$this->makeOption( '0', $no, true ),
		$this->makeOption( '1', $yes, true )
		);
		return $this->radioList ($arr, $tag_name, $tag_attribs, $selected);
	}

	public function idBox ($rowNum, $recId, $checkedOut=false, $name='cid', $selected=false) {
		$selectattr = $selected ? 'checked="checked"' : '';
		return $checkedOut ? '' : <<<IDBOX
		<input type="checkbox" class="idbox" id="cb$rowNum" name="{$name}[]" value="$recId" $selectattr />
		<input type="hidden" name="{$name}all[]" value="$recId" />
IDBOX;

	}

	public function toggleManyScript ($count) {
		if (!$this->toggleManyDone) {
			$scriptNode = <<<JSTAG

                YUI().use('*', function(Y) {
                     Y.on("click", function(e) {
                         YUI.ALIRO.CORE.checkAll($count);
                     }, "#toggle", Y);
                 });

JSTAG;

			aliroRequest::getInstance()->addScriptText($scriptNode, 'late', true);
			$this->toggleManyDone = true;
		}
	}

	public function toolTip ($tooltip, $image='tooltip.png', $text='', $href='#') {
		if (!$text) {
			$image = aliroCore::getInstance()->getCfg('live_site').'/includes/js/ThemeOffice/'.$image;
			$text = '<img src="'.$image.'" alt="Tool Tip" />';
		}
        
		return <<<CTOOLTIP
		<a class="tooltip-container" href="$href"> $text <span class="tooltip">$tooltip</span></a>
CTOOLTIP;
	}

	private function checkedOut($row, $tooltip=1) {
		if ($tooltip) {
			if (!isset($row->editor)) {
				$user = new mosUser();
				$user->load($row->checked_out);
				$row->editor = $user->name;
			}
			$date = $this->formatDate( $row->checked_out_time, '%A, %d %B %Y' );
			$time = $this->formatDate( $row->checked_out_time, '%H:%M' );
			$checked_out_text 	= <<<CHECKED_OUT
<table><tr><td>$row->editor</td></tr><tr><td>$date</td></tr><tr><td>$time</td></tr></table>
CHECKED_OUT;

			$hover = "onmouseover=\"YUI.ALIRO.COREUI.tooltip.displayAdvTooltip.call.call(this, '$checked_out_text', 'Checked Out', null, 'TL', 'TR');\"";
		}
		else $hover = '';
		$template = aliroRequest::getInstance()->getTemplateObject();
		if (method_exists($template, 'checkedOut')) return $template->checkedOut($hover);
		return '<img src="images/checked_out.png" '. $hover .' alt="Checked Out"/>';
	}

	public function formatDate ($date, $format='', $offset=''){
	    $core = aliroCore::getInstance();
		// Format was originally set to %Y-%m-%d %H:%M:%S
		if (!$offset) $offset = $core->getCfg('offset');
		if ($date AND preg_match( "/([0-9]{4})-([0-9]{2})-([0-9]{2})[ ]([0-9]{2}):([0-9]{2}):([0-9]{2})/", $date, $regs ) ) {
		    $date = mktime( $regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1] );
			$date = $date > -1 ? aliroLanguage::getInstance()->getDate($format, $date + ($offset*60*60)) : '-';
		}
		return $date;
	}

	public function checkedOutProcessing ($row, $i) {
		if (!empty($row->checked_out)) $checked = $this->checkedOut ($row);
		else $checked = $this->idBox ($i, $row->id, (!empty($row->checked_out) AND $row->checked_out != aliroUser::getInstance()->id));
		return $checked;
	}

	public function publishedProcessing ($row, $i) {
		$template = aliroRequest::getInstance()->getTemplateObject();
		if (method_exists($template, 'publishedProcessing')) return $template->publishedProcessing($row, $i);
		$img 	= $row->published ? 'publish_g.png' : 'publish_x.png';
		$task 	= $row->published ? 'unpublish' : 'publish';
		$alt 	= $row->published ? T_('Published') : T_('Unpublished');
		$action	= $row->published ? T_('Unpublish Item') : T_('Publish item');
		
		//Click event is abstracted and handed in aliro_core.js
		return <<<PUBLISH_LINK
		<a href="#" id="cb{$i}__{$task}" class="publish-processing-link" title="$action">
		    <img src="images/$img" border="0" alt="$alt" />
		</a>
PUBLISH_LINK;

	}

	public function moderatedProcessing ($row, $i) {
		$template = aliroRequest::getInstance()->getTemplateObject();
		if (method_exists($template, 'moderatedProcessing')) return $template->moderatedProcessing($row, $i);
		$img 	= $row->moderated ? 'publish_g.png' : 'publish_x.png';
		$task 	= $row->moderated ? 'unmoderate' : 'moderate';
		$alt 	= $row->published ? T_('Moderated') : T_('Unmoderated');
		$action	= $row->published ? T_('Unmoderate Item') : T_('Moderate item');
		
		//Click event is abstracted and handed in aliro_core.js
		return <<<PUBLISH_LINK
		<a href="#" id="cb{$i}__{$task}" class="publish-processing-link" title="$action">
		    <img src="images/$img" border="0" alt="$alt" />
		</a>
PUBLISH_LINK;

	}

	public function loadCalendar() {
		$live_site = aliroCore::getInstance()->getCfg('live_site');
		$tags = <<<END_TAGS
		<link rel="stylesheet" type="text/css" media="all" href="$live_site/extclasses/js/calendar/calendar-mos.css" title="green" />
		<!-- import the calendar script -->
		<script type="text/javascript" src="$live_site/extclasses/js/calendar/calendar.js"></script>
		<!-- import the language module -->
		<script type="text/javascript" src="$live_site/extclasses/js/calendar/lang/calendar-en.js"></script>
END_TAGS;
		aliroRequest::getInstance()->addCustomHeadTag ($tags);
	}
	
	//Creates tabs using YUI.  YUI3 doesn't have a tab widget yet so we wrap the YUI2 widget in the YUI3 sandbox.
	//Note: This requires that the DOM structure setup for the tabs was done so according to the required format 
	//detailed here - http://developer.yahoo.com/yui/examples/tabview/frommarkup.html
	public function tabsFromMarkup($tabContainerId) {
	    $aliroCore = aliroCore::getInstance();
        
        //Load up some extra YUI modules (required by gallery-yui2)
	    $yuiReqs = array('loader','node-base','get','async-queue');
	    aliroResourceLoader::getInstance()->addYUIModule($yuiReqs);
		
	    $scriptText = <<<JSTAG
            YUI().use('*', function(Y) {
                //Override default config to assure Gallery 2 loads YUI modules locally
 			    YAHOO_config = {
             		base: '{$aliroCore->getCfg('live_site')}/extclasses/yui/lib/'+YUI.ALIRO.CORE.get("yui2version")+'/build/',
             		combine: false
             	};

     		    //Using YUI 2 modules within a YUI 3 sandbox object
                YUI({
                     base: '{$aliroCore->getCfg('live_site')}/extclasses/yui/lib/'+YUI.ALIRO.CORE.get("yui3version")+'/build/',
                     modules: {
                         'gallery-yui2': {
                             fullpath: '{$aliroCore->getCfg('live_site')}/extclasses/yui/lib/gallery-modules/gallery-yui2.js',
                             base: '{$aliroCore->getCfg('live_site')}/extclasses/yui/lib/'+YUI.ALIRO.CORE.get("yui2version")+'/build/',
                             requires: ['node-base','get','async-queue'],
                             optional: [],
                             supersedes: []
                         }
                     }
                }).use('gallery-yui2', function (Y) {
                     Y.yui2().use("element", "tabview", function () {
                 	    var tabView = new YAHOO.widget.TabView('{$tabContainerId}');
                 	});
                });
            });   
JSTAG;

        aliroRequest::getInstance()->addScriptText($scriptText, 'late', true);
	}

}