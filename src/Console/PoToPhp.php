<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Console;

/**
 * Read a messages.po file and create strings.php in the same directory
 */
class PoToPhp extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	const DQ_ESCAPE = "__DQ__";

	protected function getHelp()
	{
		$help = <<<HELP
console php2po - Generate a strings.php file from a messages.po file
Usage
	bin/console php2po <path/to/messages.po> [-h|--help|-?] [-v]

Description
	Read a messages.po file and create the according strings.php in the same directory

Options
	-h|--help|-?  Show help information
	-v            Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		$pofile = realpath($this->getArgument(0));

		if (!file_exists($pofile)) {
			throw new \RuntimeException('Supplied file path doesn\'t exist.');
		}

		if (!is_writable(dirname($pofile))) {
			throw new \RuntimeException('Supplied directory isn\'t writable.');
		}

		$outfile = dirname($pofile) . DIRECTORY_SEPARATOR . 'strings.php';

		if (basename(dirname($pofile)) == 'C') {
			$lang = 'en';
		} else {
			$lang = str_replace('-', '_', basename(dirname($pofile)));
		}

		$this->out('Out to ' . $outfile);

		$out = "<?php\n\n";

		$infile = file($pofile);
		$k = '';
		$v = '';
		$arr = false;
		$ink = false;
		$inv = false;
		$escape_s_exp = '|[^\\\\]\$[a-z]|';

		foreach ($infile as $l) {
			$l = str_replace('\"', self::DQ_ESCAPE, $l);
			$len = strlen($l);
			if ($l[0] == "#") {
				$l = "";
			}

			if (substr($l, 0, 15) == '"Plural-Forms: ') {
				$match = [];
				preg_match("|nplurals=([0-9]*); *plural=(.*?)[;\\\\]|", $l, $match);
				$return = $this->convertCPluralConditionToPhpReturnStatement($match[2]);
				// define plural select function if not already defined
				$fnname = 'string_plural_select_' . $lang;
				$out .= 'if(! function_exists("' . $fnname . '")) {' . "\n";
				$out .= 'function ' . $fnname . '($n){' . "\n";
				$out .= '	$n = intval($n);' . "\n";
				$out .= '	' . $return . "\n";
				$out .= '}}' . "\n";
			}

			if ($k != '' && substr($l, 0, 7) == 'msgstr ') {
				if ($ink) {
					$ink = false;
					$out .= '$a->strings["' . $k . '"] = ';
				}

				if ($inv) {
					$out .= '"' . $v . '"';
				}

				$v = substr($l, 8, $len - 10);
				$v = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $v);

				$inv = true;
			}

			if ($k != "" && substr($l, 0, 7) == 'msgstr[') {
				if ($ink) {
					$ink = false;
					$out .= '$a->strings["' . $k . '"] = ';
				}
				if ($inv) {
					$inv = false;
					$out .= '"' . $v . '"';
				}

				if (!$arr) {
					$arr = true;
					$out .= "[\n";
				}

				$match = [];
				preg_match("|\[([0-9]*)\] (.*)|", $l, $match);
				$out .= "\t"
					. preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $match[1])
					. ' => '
					. preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $match[2])
					. ",\n";
			}

			if (substr($l, 0, 6) == 'msgid_') {
				$ink = false;
				$out .= '$a->strings["' . $k . '"] = ';
			}

			if ($ink) {
				$k .= trim($l, "\"\r\n");
				$k = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $k);
			}

			if (substr($l, 0, 6) == 'msgid ') {
				if ($inv) {
					$inv = false;
					$out .= '"' . $v . '"';
				}

				if ($k != "") {
					$out .= ($arr) ? "];\n" : ";\n";
				}

				$arr = false;
				$k = str_replace("msgid ", "", $l);
				if ($k != '""') {
					$k = trim($k, "\"\r\n");
				} else {
					$k = '';
				}

				$k = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $k);
				$ink = true;
			}

			if ($inv && substr($l, 0, 6) != "msgstr") {
				$v .= trim($l, "\"\r\n");
				$v = preg_replace_callback($escape_s_exp, [$this, 'escapeDollar'], $v);
			}
		}

		if ($inv) {
			$out .= '"' . $v . '"';
		}

		if ($k != '') {
			$out .= ($arr ? "];\n" : ";\n");
		}

		$out = str_replace(self::DQ_ESCAPE, '\"', $out);
		if (!file_put_contents($outfile, $out)) {
			throw new \RuntimeException('Unable to write to ' . $outfile);
		}

		return 0;
	}

	private function escapeDollar($match)
	{
		return str_replace('$', '\$', $match[0]);
	}

	/**
	 * Converts C-style plural condition in .po files to a PHP-style plural return statement
	 *
	 * Adapted from https://github.com/friendica/friendica/issues/9747#issuecomment-769604485
	 * Many thanks to Christian Archer (https://github.com/sunchaserinfo)
	 *
	 * @param string $cond
	 * @return string
	 */
	private function convertCPluralConditionToPhpReturnStatement(string $cond)
	{
		$cond = str_replace('n', '$n', $cond);

		/**
		 * Parses the condition into an array if there's at least a ternary operator, to a string otherwise
		 *
		 * Warning: Black recursive magic
		 *
		 * @param string $string
		 * @param array|string $node
		 */
		function parse(string $string, &$node = [])
		{
			// Removes extra outward parentheses
			if (strpos($string, '(') === 0 && strrpos($string, ')') === strlen($string) - 1) {
				$string = substr($string, 1, -1);
			}

			$q = strpos($string, '?');
			$s = strpos($string, ':');

			if ($q === false && $s === false) {
				$node = $string;
				return;
			}

			if ($q === false || $s < $q) {
				list($then, $else) = explode(':', $string, 2);
				$node['then'] = $then;
				$parsedElse = [];
				parse($else, $parsedElse);
				$node['else'] = $parsedElse;
			} else {
				list($if, $thenelse) = explode('?', $string, 2);
				$node['if'] = $if;
				parse($thenelse, $node);
			}
		}

		/**
		 * Renders the parsed condition tree into a return statement
		 *
		 * Warning: Black recursive magic
		 *
		 * @param $tree
		 * @return string
		 */
		function render($tree)
		{
			if (is_array($tree)) {
				$if = trim($tree['if']);
				$then = trim($tree['then']);
				$else = render($tree['else']);

				return "if ({$if}) { return {$then}; } else {$else}";
			}

			$tree = trim($tree);

			return " { return {$tree}; }";
		}

		$tree = [];
		parse($cond, $tree);

		return is_string($tree) ? "return intval({$tree});" : render($tree);
	}
}
