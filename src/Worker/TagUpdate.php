<?php
namespace Friendica\Worker;

require_once("include/tags.php");

class TagUpdate {
	public static function execute() {
		update_items();
	}
}
