<?php

/**
* Useful HTML class for admin side components
*/
class basicAdminHTML extends aliroBasicHTML  {
	protected $core = '';
	protected $act = '';

	function __construct ($controller) {
		if (!in_array($_SERVER['REMOTE_ADDR'], array('213.60.249.60'))) error_reporting(E_ERROR|E_PARSE|E_USER_ERROR);

		parent::__construct($controller);
		if ($this->core = strtolower($this->getParam($_REQUEST,'core'))) {
			$this->optionline = "<input type='hidden' name='core' value='$this->core' />";
			$this->optionurl = 'index.php?core='.$this->core;
		}
		if (!empty($controller->act)) {
			$this->act = $controller->act;
			$this->optionurl .= '&amp;act='.$this->act;
		}
	}

	protected function adminHeader ($heading, $subhead='') {
		if ($subhead) $subhead = "<small>[$subhead]</small>";
		return <<<HEAD_HTML

		<table class="adminheading">
		<tr>
			<th class="user">
			$heading $subhead
			</th>
		</tr>
		</table>

HEAD_HTML;
		
	}

}

class advancedAdminHTML extends basicAdminHTML {

	protected function listHTML ($tablename, $title, $rows, $keyname, $needlink=true) {

		$rowcount = count($rows);
		$excludes =  isset($this->controller->list_exclude) ? $this->controller->list_exclude : array();
		$change_headings = isset($this->controller->column_names) ? $this->controller->column_names : array();
        
		$html = <<<END_OF_HEADER_HTML1

		<form action="index.php" method="post" id="adminForm" name="adminForm">

		<table class="adminheading">
		<thead>
		<tr>
			<th class="user">
			$title
			</th>
		</tr>
		</thead>
		<tbody><tr><td></td></tr></tbody>
		</table>
		
		<table class="adminlist">
		<thead>
		<tr>
			<th width="3%" class="title">
			<input type="checkbox" id="toggle" name="toggle" value="" />
			</th>
END_OF_HEADER_HTML1;

		$fields = $this->getTableInfo($tablename);
		foreach ($fields as $field) {
			if (in_array($field->Field, $excludes)) continue;
			$fieldname = isset($change_headings[$field->Field]) ? $change_headings[$field->Field] : strtoupper($field->Field[0]).substr($field->Field,1);
			$html .= <<<HEADING_ITEM

			<th class="title">
			$fieldname
			</th>

HEADING_ITEM;

		}

		$html .= <<<END_OF_HEADER_HTML2

		</tr>
		</thead>
		<tbody>

END_OF_HEADER_HTML2;

		$k = 0;
		foreach ($rows as $i=>$row) {

			$html .= <<<END_OF_BODY_HTML

			<tr class="row$k">
				<td>
					{$this->html('idBox', $i, $row->$keyname)}
				</td>

END_OF_BODY_HTML;

			foreach ($fields as $field) {
			if (in_array($field->Field, $excludes)) continue;
				$fieldname = $field->Field;
				$method = 'list_'.$fieldname;
				if (method_exists($this, $method)) $fieldvalue = $this->$method($row->$fieldname, $row->$keyname);
				else $fieldvalue = strip_tags($row->$fieldname);
				if ($needlink AND $fieldname != $keyname) {
					$fieldvalue = "<a href='$this->optionurl&task=edit&id={$row->$keyname}'>$fieldvalue</a>";
					$needlink = false;
				}
				$html .= "\n\t\t\t<td>$fieldvalue</td>";
			}
			$html .= "\n\t\t</tr>";

			$k = 1 - $k;
		}
		$pagenavtext = $this->pageNav->getListFooter();

		$html .= <<<END_OF_FINAL_HTML

		</tbody>
		</table>
		$pagenavtext
		$this->optionline
		$this->formstamp
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" id="boxchecked" name="boxchecked" value="0" />
		<input type="hidden" id="hidemainmenu" name="hidemainmenu" value="0" />
		</form>
END_OF_FINAL_HTML;

		return $html;

	}

	protected function editornewHeader ($title) {
		return <<<HTML

		<table class="adminheading">
		<tr>
			<th>
				$title
			</th>
		</tr>
		</table>

		<table width="100%">
		<tr valign="top">
			<td width="60%">
				<table class="adminform">

HTML;
	}

	protected function editornewFooter () {
		return <<<HTML

		</table>
		$this->optionline
		$this->formstamp
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" id="hidemainmenu" name="hidemainmenu" value="1" />

HTML;

	}

	protected function newHTML ($tablename, $title, $keyname) {

		$html = $this->editornewHeader($title);
		$editor = aliroEditor::getInstance();

		$fields = $this->getTableInfo($tablename);
		foreach ($fields as $field) if ($field->Field != $keyname) {
			$field->Field[0] = strtoupper($field->Field[0]);

			if (false === strpos($field->Type, 'text')) $html .= <<<ITEM_HTML

				<tr>
					<td width="10%" align="right"><label for="field_$field->Field">$field->Field</label></td>
					<td width="80%">
					<input id="field_$field->Field" class="inputbox" type="text" name="$field->Field" size="60" maxlength="255" />
					</td>
				</tr>

ITEM_HTML;

			else {
				$editor->getEditorContents( $fieldname, $fieldname );
				$html .= <<<TEXT_HTML

				<tr>
					<td width="10%" align="right"><label for="field_$field->Field">$field->Field</label></td>
					<td width="80%">
					{$editor->editorAreaText('$fieldname', '', '$fieldname', 500, 300, 100, 8)}
					</td>
				</tr>

TEXT_HTML;

			}
				//	<textarea id="field_$field->Field" class="inputbox" name="$field->Field" rows="10" cols="60"></textarea>
		}

		$html .= $this->editornewFooter();

		return $html;
	}

	protected function editHTML ($tablename, $title, $keyname, $row) {

		$html = $this->editornewHeader($title);
		$editor = aliroEditor::getInstance();
		$fields = $this->getTableInfo($tablename);
		foreach ($fields as $field) if ($field->Field != $keyname) {
			$fieldname = $field->Field;
			$field->Field[0] = strtoupper($field->Field[0]);
			if (false === strpos($field->Type, 'text')) $html .= <<<ITEM_HTML

				<tr>
					<td width="10%" align="right"><label for="field_$field->Field">$field->Field</label></td>
					<td width="80%">
					<input id="field_$field->Field" class="inputbox" type="text" name="$field->Field" value="{$row->$fieldname}" size="60" maxlength="255" />
					</td>
				</tr>

ITEM_HTML;

			else {
				$editor->getEditorContents( $fieldname, $fieldname );
				$html .= <<<TEXT_HTML

				<tr>
					<td width="10%" align="right"><label for="field_$field->Field">$field->Field</label></td>
					<td width="80%">
					{$editor->editorAreaText('$fieldname', '', '$fieldname', 500, 300, 80, 8)}
					</td>
				</tr>

TEXT_HTML;

			}
				//	<textarea id="field_$field->Field" class="inputbox" name="$field->Field" rows="10" cols="60">{$row->$fieldname}</textarea>
		}

		$html .= $this->editornewFooter();

		return $html;
	}

}

class widgetAdminHTML extends advancedAdminHTML {

	function tickBox ($object, $property) {
		if (is_object($object) AND $object->$property) $checked = "checked='checked'";
		else $checked = '';
		echo "<td><input type='checkbox' name='$property' value='1' $checked /></td>";
	}

	function yesNoList ($object, $property) {
		$yesno[] = mosHTML::makeOption( 0, _NO );
		$yesno[] = mosHTML::makeOption( 1, _YES );
		if ($object) $default = $object->$property;
		else $default = 0;
		echo '<td valign="top">';
		echo mosHTML::selectList($yesno, $property, 'class="inputbox" size="1"', 'value', 'text', $default);;
		echo '</td></tr>';
	}

	function inputTop ($title, $redstar=false, $maxsize=0) {
		?>
		<tr>
		  	<td width="30%" valign="top" align="right">
				<b><?php if ($redstar) echo '<font color="red">*</font>'; echo $title; if ($maxsize) echo "</b>&nbsp;<br /><i>$maxsize</i>&nbsp;"; ?></b>&nbsp;
			</td>
		<?php
	}

	function blankRow () {
		?>
			<tr><td>&nbsp;</td></tr>
		<?php
	}

	function fileInputBox ($title, $name, $value, $width, $tooltip=null) {
		$this->inputTop($title);
		?>
			<td align="left" valign="top">
				<input class="inputbox" type="text" name="<?php echo $name; ?>" size="<?php echo $width; ?>" value="<?php echo $value; ?>" />
				<?php if ($tooltip) echo $this->tooltip($tooltip); ?>
			</td>
		</tr>
		<?php
	}

	function fileInputArea ($title, $maxsize, $name, $value, $rows, $cols, $editor=false, $tooltip=null) {
		$this->inputTop ($title, false, $maxsize);
		echo '<td valign="top">';
		if ($editor) {
			$editorclass = aliroEditor::getInstance();
			$editorclass->editorArea( 'description', $value, $name, 500, 200, $rows, $cols );
		}
		else echo "<textarea class='inputbox' name='$name' rows='$rows' cols='$cols'>$value</textarea>";
		if ($tooltip) echo $this->tooltip($tooltip);
		echo '</td></tr>';
	}

	function tickBoxField ($object, $property, $title) {
		?>
		<tr>
			<td width="30%" valign="top" align="right">
				<b><?php echo $title; ?></b>&nbsp;
			</td>
		<?php
		$this->tickBox($object,$property);
		echo '</tr>';
	}

	function simpleTickBox ($title, $name, $checked=false) {
		$this->inputTop($title);
		if ($checked) $check = 'checked="checked"';
		else $check = '';
		?>
			<td>
				<input type="checkbox" name="<?php echo $name; ?>" value="1" <?php echo $check; ?> />
			</td>
		</tr>
		<?php
	}
	function formStart ($title, $imagepath) {
		?>
		
		<form action="index2.php" method="post" id="adminForm" name="adminForm">
		<table class="adminheading">
   		<tr>
			<th><?php echo $title; ?></th>
    	</tr>
		<?php
	}

	function listHeadingStart ($count) {
	    $scriptText = <<<JSTAG
            
            YUI().use('*', function(Y) {
                 Y.on("click", function(e) { 
                     YUI.ALIRO.CORE.checkAll($count);
                 }, "#toggle", Y);
             });
                 
JSTAG;

        $this->addScriptText($scriptText, 'late', true);
		?>
		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">
			<tr>
				<th width="5" align="left">
					<input type="checkbox" id="toggle" name="toggle" value="" />
				</th>
		<?php
	}

	function headingItem ($width, $title, $colspan=1) {
		if ($colspan > 1) $colcode = " colspan=\"$colspan\"";
		else $colcode = '';
		echo "<th width=\"$width\" align=\"left\"$colcode>$title</th>";
	}

	function commonScripts ($edit_fields) {
		?>
		<script type="text/javascript">
        function submitbutton(pressbutton) {
            <?php
            $editor = aliroEditor::getInstance();
			if (is_array($edit_fields)) foreach ($edit_fields as $field) $editor->getEditorContents( $field, $field );
			else $editor->getEditorContents ($edit_fields, $edit_fields);
			?>
            YUI.ALIRO.CORE.submitform( pressbutton );
        }
        </script>
        <?php
	}

	function listFormEnd ($pagecontrol=true) {
		if ($pagecontrol) {
			?>
			<tr>
	    		<th align="center" colspan="10"> <?php echo $this->pageNav->writePagesLinks(); ?></th>
			</tr>
			<tr>
				<td align="center" colspan="10"> <?php echo $this->pageNav->writePagesCounter(); ?></td>
			</tr>
			<?php
		}
		?>
		</table>
		<div>
		<?php echo $this->optionline; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="act" value="<?php echo $this->act; ?>" />
		<input type="hidden" id="boxchecked" name="boxchecked" value="0" />
		</div>
		</form>
		<?php
	}

	function editFormEnd ($id) {
		echo $this->optionline;
		?>
		</table>
		<div>
		<input type="hidden" name="cid" value="<?php echo $id; ?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="act" value="<?php echo $this->act; ?>" />
		</div>
		</form>
		<?php
	}

	function multiOptionList ($name, $title, $options, $current, $tooltip=null) {
		$alternatives = explode(',',$options);
		$already = explode(',', $current);
		?>
		<tr>
	    <td width="30%" valign="top" align="right">
	  	<b><?php echo $title; ?></b>&nbsp;
	    </td>
	    <td valign="top">
		<?php
		foreach ($alternatives as $one) {
			if (in_array($one,$already)) $mark = 'checked="checked"';
			else $mark = '';
			$value = $name.'_'.$one;
			echo "<input type=\"checkbox\" name=\"$value\" $mark />$one";
		}
		if ($tooltip) echo '&nbsp;'.$this->tooltip($tooltip);
		echo '</td></tr>';
	}

	function tooltip ($text) {
		return '<a href="javascript:void(0)"  onmouseover="return escape('."'".$text."'".')">'.aliroCore::getInstance()->getCfg('live_site').'/includes/js/ThemeOffice/tooltip.png</a>';
	}

}