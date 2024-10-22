<?php
/**
* @package MiaCMS
* @author MiaCMS see README.php
* @copyright see README.php
* See COPYRIGHT.php for copyright notices and details.
* @license GNU/GPL Version 2, see LICENSE.php
* MiaCMS is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2 of the License.
*/

class mod_latestnews implements ifAliroModule {

	public function activate ($module, &$content, $area, $params) {
		
		$cache = new aliroCache('mod_latestnews');
		$content = $cache->get(array($area,$params));
		// If the cache returned us the desired content we can return immediately
		if ($content) return;

		$acl = aliroAuthoriser::getInstance();
		$my = aliroUser::getInstance();
		$sef = aliroSEF::getInstance();
		$mainframe = mosMainFrame::getInstance();

		$type 		= intval( $params->get( 'type', 1 ) );
		$count 		= intval( $params->get( 'count', 5 ) );
		$count 		= intval( $params->get( 'count', 5 ) );
		$catid 		= trim( $params->get( 'catid' ) );
		$secid 		= trim( $params->get( 'secid' ) );
		$show_front	= $params->get( 'show_front', 1 );
		$class_sfx	= $params->get( 'moduleclass_sfx' );

		$now 		= date( 'Y-m-d H:i:s', time() + $mainframe->getCfg('offset') * 60 * 60 );

		$access = !$mainframe->getCfg( 'shownoauth' );
		$viewAccess = ($my->gid >= $acl->get_group_id( 'Registered', 'ARO' ) ? 1 : 0) + ($my->gid >= $acl->get_group_id( 'Author', 'ARO' ) ? 1 : 0);

		// select between Content Items, Static Content or both
		switch ( $type ) {
			case 2: //Static Content only
				$query = "SELECT a.id, a.title"
				. "\n FROM #__content AS a"
				. "\n WHERE ( a.state = '1' AND a.checked_out = '0' AND a.sectionid = '0' )"
				. "\n AND ( a.publish_up = '0000-00-00 00:00:00' OR a.publish_up <= '". $now ."' )"
				. "\n AND ( a.publish_down = '0000-00-00 00:00:00' OR a.publish_down >= '". $now ."' )"
				. ( $access ? "\n AND a.access <= '". $viewAccess ."'" : '' )
				. "\n ORDER BY a.created DESC LIMIT $count"
				;
				break;

			case 3: //Both
				$query = "SELECT a.id, a.title, a.sectionid"
				. "\n FROM #__content AS a"
				. "\n WHERE ( a.state = '1' AND a.checked_out = '0' )"
				. "\n AND ( a.publish_up = '0000-00-00 00:00:00' OR a.publish_up <= '". $now ."' )"
				. "\n AND ( a.publish_down = '0000-00-00 00:00:00' OR a.publish_down >= '". $now ."' )"
				. ( $access ? "\n AND a.access <= '". $viewAccess ."'" : '' )
				. "\n ORDER BY a.created DESC LIMIT $count"
				;
			break;

			case 1:  //Content Items only
			default:
				$query = "SELECT a.id, a.title, a.sectionid, a.catid"
				. "\n FROM #__content AS a"
				. "\n LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id"
				. "\n WHERE ( a.state = '1' AND a.checked_out = '0' AND a.sectionid > '0' )"
				. "\n AND ( a.publish_up = '0000-00-00 00:00:00' OR a.publish_up <= '". $now ."' )"
				. "\n AND ( a.publish_down = '0000-00-00 00:00:00' OR a.publish_down >= '". $now ."' )"
				. ( $access ? "\n AND a.access <= '". $viewAccess ."'" : '' )
				. ( $catid ? "\n AND ( a.catid IN (". $catid .") )" : '' )
				. ( $secid ? "\n AND ( a.sectionid IN (". $secid .") )" : '' )
				. ( $show_front == "0" ? "\n AND f.content_id IS NULL" : '' )
				. "\n ORDER BY a.created DESC LIMIT $count"
				;
				break;
		}

		$rows = aliroDatabase::getInstance()->doSQLget($query);
		if (empty($rows)) return;
		
		// Output
		$lines = '';
		foreach ( $rows as $row ) {
			$link = $sef->sefRelToAbs(htmlentities('index.php?option=com_content&task=view&id='.$row->id));
			$lines .= <<<ONE_ITEM
			
			<li class="latestnews$class_sfx">
				<a href="$link" class="latestnews$class_sfx">
					$row->title
				</a>
			</li>
		
ONE_ITEM;

		}
		$content = <<<LATEST_NEWS
		
		<ul class="latestnews$class_sfx">
			$lines
		</ul>
		
LATEST_NEWS;

		$cache->save($content);
	}
}
