<?php

class listMambotsHTML extends advancedAdminHTML {
	protected $DBname = 'aliroCoreDatabase';

	// Required because gettext does not find T_('abc') inside heredoc
	public function __construct ($controller) {
		parent::__construct($controller);
		$this->translations['Published:'] = T_('Published:');
	}
	public function view ($rows) {
		echo $this->listHTML ('#__mambots', 'Aliro current plugins', $rows, 'id', false);
	}

	public function list_published ($published, $key) {
		if ($published) return <<<PUBLISHED
		<a href="$this->optionurl&amp;task=unpublish&amp;id=$key">
		<img style="border:0" src='{$this->getCfg('admin_site')}/images/publish_g.png' alt='published' />
		</a>
PUBLISHED;
		else return <<<NOT_PUBLISHED
		<a href="$this->optionurl&amp;task=publish&amp;id=$key">
		<img style="border:0" src="{$this->getCfg('admin_site')}/images/publish_x.png" alt="not published" />
		</a>
NOT_PUBLISHED;
	}

	public function list_name ($name, $key) {
		return <<<HTML
		<a href="{$this->getCfg('admin_site')}/index.php?core=cor_mambots&amp;task=edit&amp;id=$key">$name</a>
HTML;
	}

	public function edit ($id, $params, $published) {
		$checked = $published ? 'checked="checked"' : '';

		$html = $this->editornewHeader(T_('Edit Plugin'));
		$html .= <<<EDIT_HTML

		<div>
		<label for="published">
		{$this->T_('Published:')}
		</label>
		<input type="checkbox" $checked id="published" name="published" value="1" />
		</div>
		{$params->render()}

EDIT_HTML;

		$html .= $this->editornewFooter();
		echo $html;
	}

}