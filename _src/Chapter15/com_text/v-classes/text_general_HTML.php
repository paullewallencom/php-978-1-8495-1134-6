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

// User side view class - this is a very simple illustration
class text_general_HTML extends textHTML {

	public function view ($text) {
		echo $this->textForView($text);
	}
	
	public function textForView ($text) {
		$headline = $text->headline ? "<h2>$text->headline</h2>" : '';
		$byline = $text->byline ? $this->buildByline($text) : '';
		$template = $this->getTemplateObject();
		aliroMambotHandler::getInstance()->trigger('fixMarkDown', $text);
		if (method_exists($template, 'showArticle')) return <<<TEMPLATE_HTML

		<!-- Start of HTML for component text -->
		<div id="simpletext">
			{$template->showArticle($headline, $byline, $text->article)}
		</div>
		<!-- End of HTML for component text -->

TEMPLATE_HTML;

		else return <<<MODEL_HTML

		<!-- Start of HTML for component text -->
		<div id="simpletext">
			$headline
			<div>
				$text->article
			</div>
		</div>
		<!-- End of HTML for component text -->

MODEL_HTML;
		
	}

	private function buildByline ($text) {
		$unixtime = strtotime($text->modified);
		$date = date('j F Y', $unixtime);
		return T_('Posted on').' '.$date.' '.T_('by').' '.$text->byline;
	}
	
	public function showTagList ($taglist, $event, $tagtexts, $tagnames) {
		$html = '';
		$sef = aliroSEF::getInstance();
		foreach ($tagtexts as $text) {
			aliroMambotHandler::getInstance()->trigger('fixMarkDown', $text);
			if ('onIntroText' == $event) {
				$html .= <<<BLOG_TEXT
				
		<!-- Start of HTML for component text -->
		<div class="simpletextblog $tagnames">
			<h2>$text->headline</h2>
			<div>
				$text->article
			</div>
		</div>
		<!-- End of HTML for component text -->

BLOG_TEXT;
		
			}
			else {
				$link = "index.php?option=com_text&task=inlist&id=$text->id&tags=$taglist";
				$link = $sef->sefRelToAbs($link);
				$html .= <<<TEXT_LINK
			
				<div>
					<a href="$link">
						$text->headline
					</a>
				</div>
			
TEXT_LINK;

			}
		}
		echo $html;
	}
	
	public function showNextPrevious ($tags, $previous, $next) {
		$sef = aliroSEF::getInstance();
		$next_text = $next ? T_('Next') : '';
		$previous_text = $previous ? T_('Previous') : '';
		$next_link = "index.php?option=com_text&task=inlist&id=$next&tags=$tags";
		$next_link = $next ? $sef->sefRelToAbs($next_link) : '';
		$previous_link = "index.php?option=com_text&task=inlist&id=$previous&tags=$tags";
		$previous_link = $previous ? $sef->sefRelToAbs($previous_link) : '';
		echo <<<NEXT_PREVIOUS
		
		<div id="textnextprevious">
			<span class="left">
				<a href="$previous_link">$previous_text</a>
			</span>
			<span class="right">
				<a href="$next_link">$next_text</a>
			</span>
		</div>
		
NEXT_PREVIOUS;

	}
	
}