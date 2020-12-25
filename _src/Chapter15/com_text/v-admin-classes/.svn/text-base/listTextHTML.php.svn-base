<?php

/**
*
* This the Text Component, designed to be installed into Aliro to store straightforward articles that do not need to be in folders, or use access control or anything fancy.
*
* Copyright in this edition belongs to Martin Brampton
* Email - counterpoint@aliro.org
* Web - http://www.aliro.org
*
* Information about Aliro can be found at http://www.aliro.org
*
*/

// An example of an admin side view class

class listTextHTML extends basicAdminHTML {

	// The view method receives an array of objects and a URL pointing to this component but without task or other details
	// The parent class provides useful information and methods:
	// 	T_() is a method that simply calls the translation function T_ but it is provided as a method so that
	//		it can be included within heredoc
	//	html() is a quick way to call methods of the aliroHTML class, giving the method as the first parameter,
	//		followed by whatever parameters are needed by the particular method
	// pageNav is a object property of the parent class and has helpful methods for page control
	// optionline is a complete hidden input line giving the value of "option" for this component
	public function view ($rows, $basicurl) {
		$rowcount = count($rows);
		$this->addCSS(_ALIRO_ADMIN_DIR.'/components/com_text/admin.text.css');
		$html = <<<ADMIN_HEADER

		{$this->header()}
		<table class="adminlist" width="100%">
		<thead>
		<tr>
			<th width="3%" class="title">
			<input type="checkbox" id="toggle" name="toggle" value="" />
			</th>
			<th>
				{$this->T_('ID')}
			</th>
			<th width="30%" class="title">
				{$this->T_('Headline')}
			</th>
			<th>
				{$this->T_('Byline')}
			</th>
			<th>
				{$this->T_('Text')}
			</th>
			<th>
				{$this->T_('Hits')}
			</th>
			<th align="left">
				{$this->T_('Published')}
			</th>
		</tr>
		</thead>
		<tbody>

ADMIN_HEADER;

        $scriptText = <<<JSTAG
        YUI().use('*', function(Y) {
             Y.on("click", function(e) { 
                 YUI.ALIRO.CORE.checkAll($rowcount);
             }, "#toggle", Y);
         });
JSTAG;

        aliroRequest::getInstance()->addScriptText($scriptText, 'late', true);

		$i = $k = 0;
		foreach ($rows as $i=>$row) {
			$html .= <<<END_OF_BODY_HTML

			<tr class="row$k">
				<td>
					{$this->html('idBox', $i, $row->id)}
				</td>
				<td align="center">
					$row->id
				</td>
				<td>
					<a href="{$this->optionurl}&amp;task=edit&amp;id=$row->id">$row->headline</a>
				</td>
				<td>
					$row->byline
				</td>
				<td>
					{$this->plainText($row->article, 35)}
				</td>
				<td align="center">
					$row->hits
				</td>
				<td align="center">
					{$this->html('publishedProcessing', $row, $i )}
				</td>
			</tr>
END_OF_BODY_HTML;

			$i++;
			$k = 1 - $k;
		}
		$html .= <<<END_OF_FINAL_HTML

		</tbody>
		</table>
		{$this->pageNav->getListFooter()}
		<input type="hidden" id="task" name="task" value="" />
		$this->optionline
		<input type="hidden" id="boxchecked" name="boxchecked" value="0" />
		<input type="hidden" id="hidemainmenu" name="hidemainmenu" value="0" />
END_OF_FINAL_HTML;

		echo $html;
	}

	private function plainText ($text, $length) {
		$dots = strlen($text) > $length-3 ? '...' : '';
		$plain = strip_tags($text);
		return $dots ? substr($plain, 0, $length-3).$dots : $plain;
	}
	
	public function edit ($text, $tagtexts, $tagheads, $clist, $clistd) {
		$subhead = $text->id ? 'ID='.$text->id : T_('New');
		$editor = aliroEditor::getInstance();
		$this->addCSS(_ALIRO_ADMIN_DIR.'/components/com_text/admin.text.css');
		echo <<<EDIT_HTML

		{$this->header($subhead)}
	<div id="simpletext1">
		<div>
			<label for="headline">{$this->T_('Headline')}</label><br />
			<input type="text" name="headline" id="headline" size="80" value="$text->headline" />
		</div>
		<div>
			<label for="byline">{$this->T_('Byline')}</label><br />
			<input type="text" name="byline" id="byline" size="80" value="$text->byline" />
		</div>
		<div>
			<label for="version">{$this->T_('Version')}</label><br />
			<input type="text" name="version" id="version" size="80" value="$text->version" />
		</div>
		<div>
			<label for="article">{$this->T_('Article text')}</label><br />
			{$editor->editorAreaText( 'article', $text->article, 'article', 500, 200, 80, 15 )}
		</div>
		<div>
			<label for="folderid">{$this->T_('This text in folder:')}</label><br />
			$clist
		</div>
		<div>
			<label for="dfolderid">{$this->T_('This text describes folder:')}</label><br />
			$clistd
		</div>
	</div>
	<div id="simpletext2">
		<fieldset>
			<legend>{$this->T_('Publishing')}</legend>
			<div>
				<label for="published">{$this->T_('Published')}</label><br />
				<input type="checkbox" name="published" id="published" value="1" {$this->checkedIfTrue($text->published)} />
			</div>
			<div>
				<label for="publishstart">{$this->T_('Start date')}</label><br />
				<input type="text" name="publish_start" id="publishstart" size="20" value="$text->publish_start" />
			</div>
			<div>
				<label for="publishend">{$this->T_('End date')}</label><br />
				<input type="text" name="publish_end" id="publishend" size="20" value="$text->publish_end" />
			</div>
		</fieldset>
		<fieldset>
			<legend>{$this->T_('Metadata')}</legend>
			<div>
				<label for="metakey">{$this->T_('Keys')}</label><br />
				<textarea name="metakey" id="metakey" rows="4" cols="40">$text->metakey</textarea>
			</div>
			<div>
				<label for="metadesc">{$this->T_('Description')}</label><br />
				<textarea name="metadesc" id="metadesc" rows="4" cols="40">$text->metadesc</textarea>
			</div>
		</fieldset>
		<fieldset>
			<legend>{$this->T_('Tags')}</legend>
			{$this->makeTagList($tagtexts, 'tags')}
		</fieldset>
		<fieldset>
			<legend>{$this->T_('This text describes')}</legend>
			{$this->makeTagList($tagheads, 'describes')}
		</fieldset>
		<input type="hidden" id="task" name="task" value="" />
		$this->optionline
	</div>
	<div id="simpletext3">
		<input type="hidden" name="id" value="$text->id" />
		<input type="hidden" id="boxchecked" name="boxchecked" value="0" />
		<input type="hidden" id="hidemainmenu" name="hidemainmenu" value="0" />
	</div>

EDIT_HTML;

	}
	
	private function header ($subhead='') {
		if ($subhead) $subhead = "<small>[$subhead]</small>";
		return <<<HEAD_HTML

		<table class="adminheading">
		<tr>
			<th class="user">
			{$this->T_('Simple Text')} $subhead
			</th>
		</tr>
		</table>

HEAD_HTML;
		
	}
	
	public function makeTagList ($tagtexts, $name, $nulloption=true) {
		$taghandler = aliroTagHandler::getInstance();
		$types = $taghandler->getTypes();
		$typehtml = '';
		foreach ($types as $type) {
			$tags = $taghandler->getTagsOrder($type);
			if ($nulloption) $optionhtml = <<<NULL_OPTION
			
				<option value="0">{$this->T_('None of these')}</option>
			
NULL_OPTION;

			else $optionhtml = '';
			foreach ($tags as $tag) {
				$selected = in_array($tag->id, $tagtexts) ? 'selected="selected"' : '';
				$optionhtml .= <<<TAG_OPTION

			
				<option value="{$tag->id}" $selected>{$tag->name}</option>
			
TAG_OPTION;

			}
			$typehtml .= <<<TAG_SELECT
			
			$type<br />
			<select name="{$name}[]" multiple="multiple">
			$optionhtml
			</select>
			
TAG_SELECT;

		}
		return $typehtml;
	}

}