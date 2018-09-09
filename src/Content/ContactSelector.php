<?php
/**
 * @file src/Content/ContactSelector.php
 */
namespace Friendica\Content;

use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * @brief ContactSelector class
 */
class ContactSelector
{
	/**
	 * @param string $current     current
	 * @param string $foreign_net network
	 */
	public static function profileAssign($current, $foreign_net)
	{
		$o = '';

		$disabled = (($foreign_net) ? ' disabled="true" ' : '');

		$o .= "<select id=\"contact-profile-selector\" class=\"form-control\" $disabled name=\"profile-assign\" >\r\n";

		$s = DBA::select('profile', ['id', 'profile-name', 'is-default'], ['uid' => $_SESSION['uid']]);
		$r = DBA::toArray($s);

		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$selected = (($rr['id'] == $current || ($current == 0 && $rr['is-default'] == 1)) ? " selected=\"selected\" " : "");
				$o .= "<option value=\"{$rr['id']}\" $selected >{$rr['profile-name']}</option>\r\n";
			}
		}
		$o .= "</select>\r\n";
		return $o;
	}

	/**
	 * @param string  $current  current
	 * @param boolean $disabled optional, default false
	 * @return object
	 */
	public static function pollInterval($current, $disabled = false)
	{
		$dis = (($disabled) ? ' disabled="disabled" ' : '');
		$o = '';
		$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

		$rep = [
			0 => L10n::t('Frequently'),
			1 => L10n::t('Hourly'),
			2 => L10n::t('Twice daily'),
			3 => L10n::t('Daily'),
			4 => L10n::t('Weekly'),
			5 => L10n::t('Monthly')
		];

		foreach ($rep as $k => $v) {
			$selected = (($k == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
		}
		$o .= "</select>\r\n";
		return $o;
	}

	/**
	 * @param string $network network
	 * @param string $profile optional, default empty
	 * @return string
	 */
	public static function networkToName($network, $profile = "")
	{
		$nets = [
			Protocol::DFRN      =>   L10n::t('DFRN'),
			Protocol::OSTATUS   =>   L10n::t('OStatus'),
			Protocol::FEED      =>   L10n::t('RSS/Atom'),
			Protocol::MAIL      =>   L10n::t('Email'),
			Protocol::DIASPORA  =>   L10n::t('Diaspora'),
			Protocol::ZOT       =>   L10n::t('Zot!'),
			Protocol::LINKEDIN  =>   L10n::t('LinkedIn'),
			Protocol::XMPP      =>   L10n::t('XMPP/IM'),
			Protocol::MYSPACE   =>   L10n::t('MySpace'),
			Protocol::GPLUS     =>   L10n::t('Google+'),
			Protocol::PUMPIO    =>   L10n::t('pump.io'),
			Protocol::TWITTER   =>   L10n::t('Twitter'),
			Protocol::DIASPORA2 =>   L10n::t('Diaspora Connector'),
			Protocol::STATUSNET =>   L10n::t('GNU Social Connector'),
			Protocol::ACTIVITYPUB => L10n::t('ActivityPub'),
			Protocol::PNUT      =>   L10n::t('pnut'),
		];

		Addon::callHooks('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $network);

		if ((in_array($network, [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) && ($profile != "")) {
			// Create the server url out of the profile url
			$parts = parse_url($profile);
			unset($parts['path']);
			$server_url = [Strings::normaliseLink(Network::unparseURL($parts))];

			// Fetch the server url
			$gcontact = DBA::selectFirst('gcontact', ['server_url'], ['nurl' => Strings::normaliseLink($profile)]);
			if (!empty($gcontact) && !empty($gcontact['server_url'])) {
				$server_url[] = Strings::normaliseLink($gcontact['server_url']);
			}

			// Now query the GServer for the platform name
			$gserver = DBA::selectFirst('gserver', ['platform', 'network'], ['nurl' => $server_url]);

			if (DBA::isResult($gserver)) {
				if (!empty($gserver['platform'])) {
					$platform = $gserver['platform'];
				} elseif (!empty($gserver['network']) && ($gserver['network'] != Protocol::ACTIVITYPUB)) {
					$platform = self::networkToName($gserver['network']);
				}

				if (!empty($platform)) {
					$networkname = $platform;

					if ($network == Protocol::ACTIVITYPUB) {
						$networkname .= ' (AP)';
					}
				}
			}
		}

		return $networkname;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 */
	public static function gender($current = "", $suffix = "")
	{
		$o = '';
		$select[''] = '';
		$select['Male'] = L10n::t('Male');
		$select['Female'] = L10n::t('Female');
		$select['Currently Male'] = L10n::t('Currently Male');
		$select['Currently Female'] = L10n::t('Currently Female');
		$select['Mostly Male'] = L10n::t('Mostly Male');
		$select['Mostly Female'] = L10n::t('Mostly Female');
		$select['Transgender'] = L10n::t('Transgender');
		$select['Intersex'] = L10n::t('Intersex');
		$select['Transsexual'] = L10n::t('Transsexual');
		$select['Hermaphrodite'] = L10n::t('Hermaphrodite');
		$select['Neuter'] = L10n::t('Neuter');
		$select['Non-specific'] = L10n::t('Non-specific');
		$select['Other'] = L10n::t('Other');
		$select['Undecided'] = L10n::t('Undecided');

		Addon::callHooks('gender_selector', $select);

		$o .= "<select name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($selection == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 */
	public static function sexualPreference($current = "", $suffix = "")
	{
		$o = '';
		$select['Males'] = L10n::t('Males');
		$select['Females'] = L10n::t('Females');
		$select['Gay'] = L10n::t('Gay');
		$select['Lesbian'] = L10n::t('Lesbian');
		$select['No Preference'] = L10n::t('No Preference');
		$select['Bisexual'] = L10n::t('Bisexual');
		$select['Autosexual'] = L10n::t('Autosexual');
		$select['Abstinent'] = L10n::t('Abstinent');
		$select['Virgin'] = L10n::t('Virgin');
		$select['Deviant'] = L10n::t('Deviant');
		$select['Fetish'] = L10n::t('Fetish');
		$select['Oodles'] = L10n::t('Oodles');
		$select['Nonsexual'] = L10n::t('Nonsexual');

		Addon::callHooks('sexpref_selector', $select);

		$o .= "<select name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($selection == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 */
	public static function maritalStatus($current = "")
	{
		$o = '';
		$select['Single'] = L10n::t('Single');
		$select['Lonely'] = L10n::t('Lonely');
		$select['Available'] = L10n::t('Available');
		$select['Unavailable'] = L10n::t('Unavailable');
		$select['Has crush'] = L10n::t('Has crush');
		$select['Infatuated'] = L10n::t('Infatuated');
		$select['Dating'] = L10n::t('Dating');
		$select['Unfaithful'] = L10n::t('Unfaithful');
		$select['Sex Addict'] = L10n::t('Sex Addict');
		$select['Friends'] = L10n::t('Friends');
		$select['Friends/Benefits'] = L10n::t('Friends/Benefits');
		$select['Casual'] = L10n::t('Casual');
		$select['Engaged'] = L10n::t('Engaged');
		$select['Married'] = L10n::t('Married');
		$select['Imaginarily married'] = L10n::t('Imaginarily married');
		$select['Partners'] = L10n::t('Partners');
		$select['Cohabiting'] = L10n::t('Cohabiting');
		$select['Common law'] = L10n::t('Common law');
		$select['Happy'] = L10n::t('Happy');
		$select['Not looking'] = L10n::t('Not looking');
		$select['Swinger'] = L10n::t('Swinger');
		$select['Betrayed'] = L10n::t('Betrayed');
		$select['Separated'] = L10n::t('Separated');
		$select['Unstable'] = L10n::t('Unstable');
		$select['Divorced'] = L10n::t('Divorced');
		$select['Imaginarily divorced'] = L10n::t('Imaginarily divorced');
		$select['Widowed'] = L10n::t('Widowed');
		$select['Uncertain'] = L10n::t('Uncertain');
		$select['It\'s complicated'] = L10n::t('It\'s complicated');
		$select['Don\'t care'] = L10n::t('Don\'t care');
		$select['Ask me'] = L10n::t('Ask me');

		Addon::callHooks('marital_selector', $select);

		$o .= '<select name="marital" id="marital-select" size="1" >';
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($selection == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}
}
