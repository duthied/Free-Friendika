<?php
/**
 * @file src/Render/FriendicaSmartyEngine.php
 */
namespace Friendica\Render;

use Friendica\Core\Addon;

define('SMARTY3_TEMPLATE_FOLDER', 'templates');

class FriendicaSmartyEngine implements ITemplateEngine
{
	static $name = "smarty3";

	public function __construct()
	{
		if (!is_writable('view/smarty3/')) {
			echo "<b>ERROR:</b> folder <tt>view/smarty3/</tt> must be writable by webserver.";
			killme();
		}
	}

	// ITemplateEngine interface
	public function replaceMacros($s, $r)
	{
		$template = '';
		if (gettype($s) === 'string') {
			$template = $s;
			$s = new FriendicaSmarty();
		}

		$r['$APP'] = get_app();

		// "middleware": inject variables into templates
		$arr = [
			"template" => basename($s->filename),
			"vars" => $r
		];
		Addon::callHooks("template_vars", $arr);
		$r = $arr['vars'];

		foreach ($r as $key => $value) {
			if ($key[0] === '$') {
				$key = substr($key, 1);
			}

			$s->assign($key, $value);
		}
		return $s->parsed($template);
	}

	public function getTemplateFile($file, $root = '')
	{
		$a = get_app();
		$template_file = get_template_file($a, SMARTY3_TEMPLATE_FOLDER . '/' . $file, $root);
		$template = new FriendicaSmarty();
		$template->filename = $template_file;

		return $template;
	}
}
