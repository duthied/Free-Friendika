<?php
/**
 * @file src/Worker/ForkHook.php
 */

namespace Friendica\Worker;

use Friendica\Core\Addon;

Class ForkHook {
	public static function execute($name, $hook_json, $data_json) {
		global $a;

		$hook = json_decode($hook_json, true);
		$data = json_decode($data_json, true);

		Addon::callSingleHook($a, $name, $hook, $data);
	}
}
