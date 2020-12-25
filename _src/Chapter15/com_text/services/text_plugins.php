<?php
/**
* Aliro version
* 
* Mambo was originally developed by Miro (www.miro.com.au) in 2000. Miro assigned the copyright in Mambo to The Mambo Foundation in 2005 to ensure
* that Mambo remained free Open Source software owned and managed by the community.
* Mambo is Free Software
*/ 

class botTextFix extends aliroPlugin {
	
	public function onIntroText ($textobject) {
		if (!$this->published) {
			$textobject->saveText($this->stripControls($text));
			return false;
		}
		$text = $textobject->getText();
		$id = $textobject->getID();
		$cmarker = $this->params->get('textctl', 'textctl');
		$regex = "/{\s*{$cmarker}\s*:[^}]*}/";
		$parts = preg_split($regex, $text, 2);
		$mtext = $this->params->get('readmore', 'Read More...');
		$morelink = $this->makeMore($id);
		$readmore = empty($parts[1]) ? '' : <<<READ_MORE
		
		<p class="textreadmore">
			<a href="$morelink">$mtext</a>
		</p>
		
READ_MORE;

		$textobject->saveText($parts[0].$readmore);
		return true;
	}

	public function onMainText ($textobject) {
		if (!$this->published) {
			$textobject->saveText($this->stripControls($text));
			return false;
		}
		$text = $textobject->getText();
		$id = $textobject->getID();
		$cmarker = $this->params->get('textctl', 'textctl');
		$regex = "/{\s*{$cmarker}\s*:\s([^}]*)}/";
		preg_match($regex, $text, $matches);
		$maintype = isset($matches[1]) ? $matches[1] : '';
		if ($maintype == $this->params->get('replaceintro', 'replaceintro')) return $this->replaceIntro($text);
		$textobject->saveText($this->stripControls($text));
		return true;
	}
	
	private function replaceIntro ($text) {
		$cmarker = $this->params->get('textctl', 'textctl');
		$regex = "/{\s*{$cmarker}\s*:[^}]*}/";
		$parts = preg_split($regex, $text, 2);
		return isset($parts[1]) ? $this->stripControls($parts[1]): $parts[0];
	}
	
	private function stripControls ($text) {
		$cmarker = $this->params->get('textctl', 'textctl');
		$regex = "/{\s*{$cmarker}\s*:[^}]*}/";
		return preg_replace($regex, '', $text);
	}
	
	private function makeMore ($id) {
		$link = 'index.php?option=com_text';
		$request = aliroRequest::getInstance();
		$task = $request->getParam($_REQUEST, 'task');
		if ('taglist' == $task) $link .= '&task=inlist';
		else $link .= '&task=display';
		$tags = $request->getParam($_REQUEST, 'tags');
		if ($tags) $link .= '&tags='.$tags;
		if ($id) $link .= '&id='.$id;
		return aliroSEF::getInstance()->sefRelToAbs($link);
	}
 
}

class botSearchText extends aliroPlugin {

	public function onSearch ($text, $phrase='', $ordering='') {

		// $_SESSION['searchword'] = $text;

		$now = date( "Y-m-d H:i:s");

		$text = trim( $text );
		if ($text == '') return array();

		$wheres = array();
		switch ($phrase) {
			case 'exact':
				$wheres2 = array();
				$wheres2[] = "LOWER(a.headline) LIKE '%$text%'";
				$wheres2[] = "LOWER(a.subhead) LIKE '%$text%'";
				$wheres2[] = "LOWER(a.article) LIKE '%$text%'";
				$wheres2[] = "LOWER(a.metakey) LIKE '%$text%'";
				$wheres2[] = "LOWER(a.metadesc) LIKE '%$text%'";
				$where = '(' . implode( ') OR (', $wheres2 ) . ')';
				break;
			case 'all':
			case 'any':
			default:
				$words = explode( ' ', $text );
				$wheres = array();
				foreach ($words as $word) {
					$wheres2 = array();
					$wheres2[] = "LOWER(a.headline) LIKE '%$word%'";
					$wheres2[] = "LOWER(a.subhead) LIKE '%$word%'";
					$wheres2[] = "LOWER(a.article) LIKE '%$word%'";
					$wheres2[] = "LOWER(a.metakey) LIKE '%$word%'";
					$wheres2[] = "LOWER(a.metadesc) LIKE '%$word%'";
					$wheres[] = implode( ' OR ', $wheres2 );
				}
				$where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
				break;
		}

		$morder = '';
		switch ($ordering) {
			case 'newest':
			default:
				$order = 'a.created DESC';
				break;
			case 'oldest':
				$order = 'a.created ASC';
				break;
			case 'popular':
				$order = 'a.hits DESC';
				break;
			case 'alpha':
			case 'category':
				$order = 'a.headline ASC';
				break;
		}

		$sql = "SELECT a.headline AS title, CONCAT(a.subhead,a.article) AS text, "
		. "\n metakey, metadesc, created, hits, "
		. "\n CONCAT( 'index.php?option=com_text&task=display&id=', a.id ) AS href, '2' AS browsernav"
		. "\n FROM #__simple_text AS a"
		. "\n WHERE ( $where )"
		. "\n AND ( publish_start <= NOW() )"
		. "\n AND ( publish_end = '0000-00-00 00:00:00' OR publish_end >= NOW() )"
		. "\n ORDER BY $order";

		return aliroDatabase::getInstance()->doSQLget($sql);
	}

}