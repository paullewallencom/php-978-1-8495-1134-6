<?php
/**
* Aliro custom module
*/

class mod_custom extends aliroFriendlyBase implements ifAliroModule {
	
	public function activate ($module, &$content, $area, $params) {
		$customtext = $params->get ('customcontent');
		$livesite = aliroRequest::getInstance()->getCfg('live_site');
		$sef = aliroSEF::getInstance();
		preg_match_all('#"(('.$livesite.'/)|(/))?index.php[^"]*"#', $customtext, $matches);
		if (isset($matches[0]) AND is_array($matches[0])) foreach ($matches[0] as $match) {
			if (1 === strpos($match, $livesite)) $url = substr($match, strlen($livesite)+2, -1);
			elseif ('/' == $match[1]) $url = substr($match, 2, -1);
			else $url = substr($match,1,-1);
			$customtext = str_replace($match, '"'.$sef->sefRelToAbs($url).'"', $customtext);
		}

		$content = <<<MAIN_HTML
			<div class='custom'>
			$customtext
			<!-- End of custom module -->
			</div>
			
MAIN_HTML;
	}
	
}
