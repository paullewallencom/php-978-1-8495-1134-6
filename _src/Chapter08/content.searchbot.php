<?php
/**
* Aliro version by Martin Brampton, April 2007
*
* Mambo was originally developed by Miro (www.miro.com.au) in 2000. Miro assigned the copyright in Mambo to The Mambo Foundation in 2005 to ensure
* that Mambo remained free Open Source software owned and managed by the community.
* Mambo is Free Software
*/

/**
* Content Search method
*
* The sql must return the following fields that are used in a common display
* routine: href, title, section, created, text, browsernav
* @param string Target search string
* @param string mathcing option, exact|any|all
* @param string ordering option, newest|oldest|popular|alpha|category
*/

class botSearchContent {

	public function perform ($event, $botparams, $published, $text, $phrase='', $ordering='') {

	$my = aliroUser::getInstance();
	$database = aliroDatabase::getInstance();

	$_SESSION['searchword'] = $text;

	$now = date( "Y-m-d H:i:s", time()+aliroCore::getInstance()->getCfg('offset')*60*60 );

	$text = trim( $text );
	if ($text == '') {
		return array();
	}

	$wheres = array();
	switch ($phrase) {
		case 'exact':
			$wheres2 = array();
			$wheres2[] = "LOWER(a.title) LIKE '%$text%'";
			$wheres2[] = "LOWER(a.introtext) LIKE '%$text%'";
			$wheres2[] = "LOWER(a.`fulltext`) LIKE '%$text%'";
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
				$wheres2[] = "LOWER(a.title) LIKE '%$word%'";
				$wheres2[] = "LOWER(a.introtext) LIKE '%$word%'";
				$wheres2[] = "LOWER(a.`fulltext`) LIKE '%$word%'";
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
			$order = 'a.title ASC';
			break;
		case 'category':
			$order = 'b.title ASC, a.title ASC';
			$morder = 'a.title ASC';
			break;
	}

	$sql = "SELECT a.title AS title,"
	. "\n a.created AS created,"
	. "\n CONCAT(a.introtext, a.`fulltext`) AS text,"
	. "\n CONCAT_WS( '/', u.title, b.title ) AS section,";
	$sql .= "\n CONCAT( 'index.php?option=com_content&task=view&id=', a.id ) AS href,";
	$sql .= "\n '2' AS browsernav"
	. "\n FROM #__content AS a"
	. "\n INNER JOIN #__categories AS b ON b.id=a.catid AND b.access <= '$my->gid'"
	. "\n LEFT JOIN #__sections AS u ON u.id = a.sectionid"
	. "\n WHERE ( $where )"
	. "\n AND a.state = '1'"
	. "\n AND a.access <= '$my->gid'"
	. "\n AND u.published = '1'"
	. "\n AND b.published = '1'"
	. "\n AND ( publish_up = '0000-00-00 00:00:00' OR publish_up <= '$now' )"
	. "\n AND ( publish_down = '0000-00-00 00:00:00' OR publish_down >= '$now' )"
	. "\n ORDER BY $order";

	$database->setQuery( $sql );

	$list = $database->loadObjectList();

	// search typed content
	$database->setQuery( "SELECT a.title AS title, a.created AS created,"
	. "\n a.introtext AS text,"
	. "\n CONCAT( 'index.php?option=com_content&task=view&id=', a.id) AS href,"
	. "\n '2' as browsernav, 'Menu' AS section"
	. "\n FROM #__content AS a"
	. "\n WHERE ($where)"
	. "\n AND a.state='1' AND a.access<='$my->gid' AND a.sectionid=0 AND a.catid=0"
	. "\n AND ( publish_up = '0000-00-00 00:00:00' OR publish_up <= '$now' )"
	. "\n AND ( publish_down = '0000-00-00 00:00:00' OR publish_down >= '$now' )"
	. "\n ORDER BY " . ($morder ? $morder : $order)
	);
	$list2 = $database->loadObjectList();

	// search archived content
	$database->setQuery( "SELECT a.title AS title,"
	. "\n a.created AS created,"
	. "\n a.introtext AS text,"
	. "\n CONCAT_WS( '/', 'Archived ', u.title, b.title ) AS section,"
	. "\n CONCAT('index.php?option=com_content&task=view&id=',a.id) AS href,"
	. "\n '2' AS browsernav"
	. "\n FROM #__content AS a"
	. "\n INNER JOIN #__categories AS b ON b.id=a.catid AND b.access <='$my->gid'"
	. "\n LEFT JOIN #__sections AS u ON u.id = a.sectionid"
	. "\n WHERE ( $where )"
	. "\n AND a.state = '-1' AND a.access <= '$my->gid'"
	. "\n AND ( publish_up = '0000-00-00 00:00:00' OR publish_up <= '$now' )"
	. "\n AND ( publish_down = '0000-00-00 00:00:00' OR publish_down >= '$now' )"
	. "\n ORDER BY $order"
	);
	$list3 = $database->loadObjectList();

	$result = array();
	if ($list) $result = array_merge($result, $list);
	if ($list2) $result = array_merge($result, $list2);
	if ($list3) $result = array_merge($result, $list3);
	return $result;
}

}

?>