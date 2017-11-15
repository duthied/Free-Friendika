<?php
namespace Friendica\Worker;

require_once("include/threads.php");

class ThreadUpdate {
	public static function execute() {
		update_threads();
		update_threads_mention();
	}
}
