<?php
/**
 * @file include/tags.php
 */

use Friendica\Content\Widget;
use Friendica\Model\Term;

function create_tags_from_item($itemid)
{
	return Term::insertFromItemId($itemid);
}

function create_tags_from_itemuri($itemuri, $uid)
{
	return Term::insertFromItemUri($itemuri, $uid);
}

function tagcloud_wall_widget($limit = 50)
{
	return Widget::tagCloud($limit);
}
