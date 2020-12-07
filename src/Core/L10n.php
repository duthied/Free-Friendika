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

namespace Friendica\Core;

use Friendica\Core\Config\IConfig;
use Friendica\Core\Session\ISession;
use Friendica\Database\Database;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Provide Language, Translation, and Localization functions to the application
 * Localization can be referred to by the numeronym L10N (as in: "L", followed by ten more letters, and then "N").
 */
class L10n
{
	/** @var string The default language */
	const DEFAULT = 'en';

	/**
	 * A string indicating the current language used for translation:
	 * - Two-letter ISO 639-1 code.
	 * - Two-letter ISO 639-1 code + dash + Two-letter ISO 3166-1 alpha-2 country code.
	 *
	 * @var string
	 */
	private $lang = '';

	/**
	 * An array of translation strings whose key is the neutral english message.
	 *
	 * @var array
	 */
	private $strings = [];

	/**
	 * @var Database
	 */
	private $dba;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(IConfig $config, Database $dba, LoggerInterface $logger, ISession $session, array $server, array $get)
	{
		$this->dba    = $dba;
		$this->logger = $logger;

		$this->loadTranslationTable(L10n::detectLanguage($server, $get, $config->get('system', 'language', self::DEFAULT)));
		$this->setSessionVariable($session);
		$this->setLangFromSession($session);
	}

	/**
	 * Returns the current language code
	 *
	 * @return string Language code
	 */
	public function getCurrentLang()
	{
		return $this->lang;
	}

	/**
	 * Sets the language session variable
	 */
	private function setSessionVariable(ISession $session)
	{
		if ($session->get('authenticated') && !$session->get('language')) {
			$session->set('language', $this->lang);
			// we haven't loaded user data yet, but we need user language
			if ($session->get('uid')) {
				$user = $this->dba->selectFirst('user', ['language'], ['uid' => $_SESSION['uid']]);
				if ($this->dba->isResult($user)) {
					$session->set('language', $user['language']);
				}
			}
		}

		if (isset($_GET['lang'])) {
			$session->set('language', $_GET['lang']);
		}
	}

	private function setLangFromSession(ISession $session)
	{
		if ($session->get('language') !== $this->lang) {
			$this->loadTranslationTable($session->get('language'));
		}
	}

	/**
	 * Loads string translation table
	 *
	 * First addon strings are loaded, then globals
	 *
	 * Uses an App object shim since all the strings files refer to $a->strings
	 *
	 * @param string $lang language code to load
	 *
	 * @throws \Exception
	 */
	private function loadTranslationTable($lang)
	{
		$lang = Strings::sanitizeFilePathItem($lang);

		// Don't override the language setting with empty languages
		if (empty($lang)) {
			return;
		}

		$a          = new \stdClass();
		$a->strings = [];

		// load enabled addons strings
		$addons = $this->dba->select('addon', ['name'], ['installed' => true]);
		while ($p = $this->dba->fetch($addons)) {
			$name = Strings::sanitizeFilePathItem($p['name']);
			if (file_exists(__DIR__ . "/../../addon/$name/lang/$lang/strings.php")) {
				include __DIR__ . "/../../addon/$name/lang/$lang/strings.php";
			}
		}

		if (file_exists(__DIR__ . "/../../view/lang/$lang/strings.php")) {
			include __DIR__ . "/../../view/lang/$lang/strings.php";
		}

		$this->lang    = $lang;
		$this->strings = $a->strings;

		unset($a);
	}

	/**
	 * Returns the preferred language from the HTTP_ACCEPT_LANGUAGE header
	 *
	 * @param string $sysLang The default fallback language
	 * @param array  $server  The $_SERVER array
	 * @param array  $get     The $_GET array
	 *
	 * @return string The two-letter language code
	 */
	public static function detectLanguage(array $server, array $get, string $sysLang = self::DEFAULT)
	{
		$lang_variable = $server['HTTP_ACCEPT_LANGUAGE'] ?? null;

		$acceptedLanguages = preg_split('/,\s*/', $lang_variable);

		if (empty($acceptedLanguages)) {
			$acceptedLanguages = [];
		}

		// Add get as absolute quality accepted language (except this language isn't valid)
		if (!empty($get['lang'])) {
			$acceptedLanguages[] = $get['lang'];
		}

		// return the sys language in case there's nothing to do
		if (empty($acceptedLanguages)) {
			return $sysLang;
		}

		// Set the syslang as default fallback
		$current_lang = $sysLang;
		// start with quality zero (every guessed language is more acceptable ..)
		$current_q = 0;

		foreach ($acceptedLanguages as $acceptedLanguage) {
			$res = preg_match(
				'/^([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i',
				$acceptedLanguage,
				$matches
			);

			// Invalid language? -> skip
			if (!$res) {
				continue;
			}

			// split language codes based on it's "-"
			$lang_code = explode('-', $matches[1]);

			// determine the quality of the guess
			if (isset($matches[2])) {
				$lang_quality = (float)$matches[2];
			} else {
				// fallback so without a quality parameter, it's probably the best
				$lang_quality = 1;
			}

			// loop through each part of the code-parts
			while (count($lang_code)) {
				// try to mix them so we can get double-code parts too
				$match_lang = strtolower(join('-', $lang_code));
				if (file_exists(__DIR__ . "/../../view/lang/$match_lang") &&
				    is_dir(__DIR__ . "/../../view/lang/$match_lang")) {
					if ($lang_quality > $current_q) {
						$current_lang = $match_lang;
						$current_q    = $lang_quality;
						break;
					}
				}

				// remove the most right code-part
				array_pop($lang_code);
			}
		}

		return $current_lang;
	}

	/**
	 * Return the localized version of the provided string with optional string interpolation
	 *
	 * This function takes a english string as parameter, and if a localized version
	 * exists for the current language, substitutes it before performing an eventual
	 * string interpolation (sprintf) with additional optional arguments.
	 *
	 * Usages:
	 * - DI::l10n()->t('This is an example')
	 * - DI::l10n()->t('URL %s returned no result', $url)
	 * - DI::l10n()->t('Current version: %s, new version: %s', $current_version, $new_version)
	 *
	 * @param string $s
	 * @param array  $vars Variables to interpolate in the translation string
	 *
	 * @return string
	 */
	public function t($s, ...$vars)
	{
		if (empty($s)) {
			return '';
		}

		if (!empty($this->strings[$s])) {
			$t = $this->strings[$s];
			$s = is_array($t) ? $t[0] : $t;
		}

		if (count($vars) > 0) {
			$s = sprintf($s, ...$vars);
		}

		return $s;
	}

	/**
	 * Return the localized version of a singular/plural string with optional string interpolation
	 *
	 * This function takes two english strings as parameters, singular and plural, as
	 * well as a count. If a localized version exists for the current language, they
	 * are used instead. Discrimination between singular and plural is done using the
	 * localized function if any or the default one. Finally, a string interpolation
	 * is performed using the count as parameter.
	 *
	 * Usages:
	 * - DI::l10n()->tt('Like', 'Likes', $count)
	 * - DI::l10n()->tt("%s user deleted", "%s users deleted", count($users))
	 *
	 * @param string $singular
	 * @param string $plural
	 * @param int    $count
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function tt(string $singular, string $plural, int $count)
	{
		if (!empty($this->strings[$singular])) {
			$t = $this->strings[$singular];
			if (is_array($t)) {
				$plural_function = 'string_plural_select_' . str_replace('-', '_', $this->lang);
				if (function_exists($plural_function)) {
					$i = $plural_function($count);
				} else {
					$i = $this->stringPluralSelectDefault($count);
				}

				// for some languages there is only a single array item
				if (!isset($t[$i])) {
					$s = $t[0];
				} else {
					$s = $t[$i];
				}
			} else {
				$s = $t;
			}
		} elseif ($this->stringPluralSelectDefault($count)) {
			$s = $plural;
		} else {
			$s = $singular;
		}

		$s = @sprintf($s, $count);

		return $s;
	}

	/**
	 * Provide a fallback which will not collide with a function defined in any language file
	 *
	 * @param int $n
	 *
	 * @return bool
	 */
	private function stringPluralSelectDefault($n)
	{
		return $n != 1;
	}

	/**
	 * Return installed languages codes as associative array
	 *
	 * Scans the view/lang directory for the existence of "strings.php" files, and
	 * returns an alphabetical list of their folder names (@-char language codes).
	 * Adds the english language if it's missing from the list.
	 *
	 * Ex: array('de' => 'de', 'en' => 'en', 'fr' => 'fr', ...)
	 *
	 * @return array
	 */
	public static function getAvailableLanguages()
	{
		$langs              = [];
		$strings_file_paths = glob('view/lang/*/strings.php');

		if (is_array($strings_file_paths) && count($strings_file_paths)) {
			if (!in_array('view/lang/en/strings.php', $strings_file_paths)) {
				$strings_file_paths[] = 'view/lang/en/strings.php';
			}
			asort($strings_file_paths);
			foreach ($strings_file_paths as $strings_file_path) {
				$path_array            = explode('/', $strings_file_path);
				$langs[$path_array[2]] = $path_array[2];
			}
		}
		return $langs;
	}

	/**
	 * Translate days and months names.
	 *
	 * @param string $s String with day or month name.
	 *
	 * @return string Translated string.
	 */
	public function getDay($s)
	{
		$ret = str_replace(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
			[$this->t('Monday'), $this->t('Tuesday'), $this->t('Wednesday'), $this->t('Thursday'), $this->t('Friday'), $this->t('Saturday'), $this->t('Sunday')],
			$s);

		$ret = str_replace(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			[$this->t('January'), $this->t('February'), $this->t('March'), $this->t('April'), $this->t('May'), $this->t('June'), $this->t('July'), $this->t('August'), $this->t('September'), $this->t('October'), $this->t('November'), $this->t('December')],
			$ret);

		return $ret;
	}

	/**
	 * Translate short days and months names.
	 *
	 * @param string $s String with short day or month name.
	 *
	 * @return string Translated string.
	 */
	public function getDayShort($s)
	{
		$ret = str_replace(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
			[$this->t('Mon'), $this->t('Tue'), $this->t('Wed'), $this->t('Thu'), $this->t('Fri'), $this->t('Sat'), $this->t('Sun')],
			$s);

		$ret = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			[$this->t('Jan'), $this->t('Feb'), $this->t('Mar'), $this->t('Apr'), $this->t('May'), $this->t('Jun'), $this->t('Jul'), $this->t('Aug'), $this->t('Sep'), $this->t('Oct'), $this->t('Nov'), $this->t('Dec')],
			$ret);

		return $ret;
	}

	/**
	 * Load poke verbs
	 *
	 * @return array index is present tense verb
	 *                 value is array containing past tense verb, translation of present, translation of past
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @hook poke_verbs pokes array
	 */
	public function getPokeVerbs()
	{
		// index is present tense verb
		// value is array containing past tense verb, translation of present, translation of past
		$arr = [
			'poke'   => ['poked', $this->t('poke'), $this->t('poked')],
			'ping'   => ['pinged', $this->t('ping'), $this->t('pinged')],
			'prod'   => ['prodded', $this->t('prod'), $this->t('prodded')],
			'slap'   => ['slapped', $this->t('slap'), $this->t('slapped')],
			'finger' => ['fingered', $this->t('finger'), $this->t('fingered')],
			'rebuff' => ['rebuffed', $this->t('rebuff'), $this->t('rebuffed')],
		];

		Hook::callAll('poke_verbs', $arr);

		return $arr;
	}

	/**
	 * Creates a new L10n instance based on the given langauge
	 *
	 * @param string $lang The new language
	 *
	 * @return static A new L10n instance
	 * @throws \Exception
	 */
	public function withLang(string $lang)
	{
		// Don't create a new instance for same language
		if ($lang === $this->lang) {
			return $this;
		}

		$newL10n = clone $this;
		$newL10n->loadTranslationTable($lang);
		return $newL10n;
	}
}
