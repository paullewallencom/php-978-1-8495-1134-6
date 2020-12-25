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
 * aliroPage404 should be developed to be much more user friendly.  At present
 * it simply provides some basic diagnostics when a page link does not work out.
 *
 */

abstract class aliroPageFail {

	protected function formatMessage ($message) {
		if ($message) return <<<FORMAT_MSG

			<h4>
				$message
			</h4>

FORMAT_MSG;

	}

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	protected function recordPageFail ($errorcode) {
		$database = aliroCoreDatabase::getInstance();
		$uri = $database->getEscaped(@$_SERVER['REQUEST_URI']);
		$timestamp = date ('Y-m-d H:i:s');
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$referer = $database->getEscaped($referer);
		$ip = aliroSession::getSession()->getIP();
		$post = base64_encode(serialize($_POST));
		$trace = aliroBase::trace();
		$database->doSQL("INSERT INTO #__error_404 (uri, timestamp, referer, ip, errortype, post, trace) VALUES ('$uri', '$timestamp', '$referer', '$ip', '$errorcode', '$post', '$trace') ON DUPLICATE KEY UPDATE timestamp = '$timestamp', referer='$referer', post='$post', trace='$trace'");
		$database->doSQL("DELETE LOW_PRIORITY FROM #__error_404 WHERE SUBDATE(NOW(), INTERVAL 14 DAY) > timestamp");
	}

	protected function searchuri () {
		$uri = @$_SERVER['REQUEST_URI'];
		$bits = explode ('/', $uri);
		for ($i=count($bits); $i>0; $i--) {
			$bit = $bits[$i-1];
			if ($bit) break;
		}
		$bit = str_replace(array('!', '%21'), array('',''), $bit);
		$searchword = preg_replace('/[^A-Za-z]/', ' ', $bit);
		$results = aliroMambotHandler::getInstance()->trigger('onSearch', array($searchword, 'all', 'popular'));
		$lines = array();
		$purifier = new HTMLPurifier;
		foreach ($results as $result) {
			if ($result) foreach ($result as $item) {
				if (empty($item->text)) continue;
				$item->text = $purifier->purify($item->text);
				$item->text = strip_tags($item->text);
				if (strlen($item->text) > 200) $item->text = substr($item->text,0,200).'...';
				if (!isset($item->section)) $item->section = '';
				$lines[] = $item;
			}
		}
		$html = '';
		$sef = aliroSEF::getInstance();
		if (count($lines)) foreach ($lines as $line) {
			$section = isset($line->section) ? $line->section : '';
			$html .= <<<SEARCH_LINE

			<p>
			<a href="{$sef->sefRelToAbs($line->href)}">$line->title</a>
			$section
			$line->text
			</p>

SEARCH_LINE;

		}
		else $html = '<p>'.T_('Sorry, none found').'</p>';
		return $html;
	}
}

class aliroPage404 extends aliroPageFail {

	public function __construct ($message='') {
		if (aliroCore::getInstance()->getCfg('debug')) echo aliroBase::trace();
		if (aliroComponentHandler::getInstance()->componentCount() AND aliroMenuHandler::getInstance()->getMenuCount()) {
			header ($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
			$this->recordPageFail('404');
			$searchtext = $this->searchuri();
			$request = aliroRequest::getInstance();
			$request->noRedirectHere();
			
			$request->setPageTitle($this->T_('404 Error - page not found'));
			echo <<<PAGE_404
			<h3>{$this->T_('Sorry! Page not found')}</h3>
			{$this->formatMessage($message)}
			<p>
			{$this->T_('This may be a problem with our system, and the issue has been logged for investigation. Or it could be that you have an outdated link.')}
			</p>
			<p>
			{$this->T_('If you have any query you would like us to deal with, please contact us')}
			</p>
			<p>
			{$this->T_('The following items have some connection with the URI you used to come here, so maybe they are what you were looking for?')}
			</p>
PAGE_404;

			echo $searchtext;
		}
		else echo $this->T_('This Aliro based web site is not yet configured with user data, please call back later');
	}
}