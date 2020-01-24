<?php
/**
 * @file src/Content/ContactSelector.php
 */
namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * ContactSelector class
 */
class ContactSelector
{
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
			0 => DI::l10n()->t('Frequently'),
			1 => DI::l10n()->t('Hourly'),
			2 => DI::l10n()->t('Twice daily'),
			3 => DI::l10n()->t('Daily'),
			4 => DI::l10n()->t('Weekly'),
			5 => DI::l10n()->t('Monthly')
		];

		foreach ($rep as $k => $v) {
			$selected = (($k == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
		}
		$o .= "</select>\r\n";
		return $o;
	}

	/**
	 * @param string $profile Profile URL
	 * @return string Server URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getServerURLForProfile($profile)
	{
		$server_url = '';

		// Fetch the server url from the contact table
		$contact = DBA::selectFirst('contact', ['baseurl'], ['uid' => 0, 'nurl' => Strings::normaliseLink($profile)]);
		if (DBA::isResult($contact) && !empty($contact['baseurl'])) {
			$server_url = Strings::normaliseLink($contact['baseurl']);
		}

		if (empty($server_url)) {
			// Fetch the server url from the gcontact table
			$gcontact = DBA::selectFirst('gcontact', ['server_url'], ['nurl' => Strings::normaliseLink($profile)]);
			if (!empty($gcontact) && !empty($gcontact['server_url'])) {
				$server_url = Strings::normaliseLink($gcontact['server_url']);
			}
		}

		if (empty($server_url)) {
			// Create the server url out of the profile url
			$parts = parse_url($profile);
			unset($parts['path']);
			$server_url = Strings::normaliseLink(Network::unparseURL($parts));
		}

		return $server_url;
	}

	/**
	 * @param string $network  network of the contact
	 * @param string $profile  optional, default empty
	 * @param string $protocol (Optional) Protocol that is used for the transmission
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networkToName($network, $profile = '', $protocol = '')
	{
		$nets = [
			Protocol::DFRN      =>   DI::l10n()->t('DFRN'),
			Protocol::OSTATUS   =>   DI::l10n()->t('OStatus'),
			Protocol::FEED      =>   DI::l10n()->t('RSS/Atom'),
			Protocol::MAIL      =>   DI::l10n()->t('Email'),
			Protocol::DIASPORA  =>   DI::l10n()->t('Diaspora'),
			Protocol::ZOT       =>   DI::l10n()->t('Zot!'),
			Protocol::LINKEDIN  =>   DI::l10n()->t('LinkedIn'),
			Protocol::XMPP      =>   DI::l10n()->t('XMPP/IM'),
			Protocol::MYSPACE   =>   DI::l10n()->t('MySpace'),
			Protocol::GPLUS     =>   DI::l10n()->t('Google+'),
			Protocol::PUMPIO    =>   DI::l10n()->t('pump.io'),
			Protocol::TWITTER   =>   DI::l10n()->t('Twitter'),
			Protocol::DISCOURSE =>   DI::l10n()->t('Discourse'),
			Protocol::DIASPORA2 =>   DI::l10n()->t('Diaspora Connector'),
			Protocol::STATUSNET =>   DI::l10n()->t('GNU Social Connector'),
			Protocol::ACTIVITYPUB => DI::l10n()->t('ActivityPub'),
			Protocol::PNUT      =>   DI::l10n()->t('pnut'),
		];

		Hook::callAll('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $network);

		if ((in_array($network, Protocol::FEDERATED)) && ($profile != "")) {
			$server_url = self::getServerURLForProfile($profile);

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

		if (!empty($protocol) && ($protocol != $network)) {
			$networkname = DI::l10n()->t('%s (via %s)', $networkname, self::networkToName($protocol));
		}

		return $networkname;
	}

	/**
	 * @param string $network network
	 * @param string $profile optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networkToIcon($network, $profile = "")
	{
		$nets = [
			Protocol::DFRN      =>   'friendica',
			Protocol::OSTATUS   =>   'gnu-social', // There is no generic OStatus icon
			Protocol::FEED      =>   'rss',
			Protocol::MAIL      =>   'inbox',
			Protocol::DIASPORA  =>   'diaspora',
			Protocol::ZOT       =>   'hubzilla',
			Protocol::LINKEDIN  =>   'linkedin',
			Protocol::XMPP      =>   'xmpp',
			Protocol::MYSPACE   =>   'file-text-o', /// @todo
			Protocol::GPLUS     =>   'google-plus',
			Protocol::PUMPIO    =>   'file-text-o', /// @todo
			Protocol::TWITTER   =>   'twitter',
			Protocol::DISCOURSE =>   'dot-circle-o', /// @todo
			Protocol::DIASPORA2 =>   'diaspora',
			Protocol::STATUSNET =>   'gnu-social',
			Protocol::ACTIVITYPUB => 'activitypub',
			Protocol::PNUT      =>   'file-text-o', /// @todo
		];

		$platform_icons = ['diaspora' => 'diaspora', 'friendica' => 'friendica', 'friendika' => 'friendica',
			'GNU Social' => 'gnu-social', 'gnusocial' => 'gnu-social', 'hubzilla' => 'hubzilla',
			'mastodon' => 'mastodon', 'peertube' => 'peertube', 'pixelfed' => 'pixelfed',
			'pleroma' => 'pleroma', 'red' => 'hubzilla', 'redmatrix' => 'hubzilla',
			'socialhome' => 'social-home', 'wordpress' => 'wordpress'];

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$network_icon = str_replace($search, $replace, $network);

		if ((in_array($network, Protocol::FEDERATED)) && ($profile != "")) {
			$server_url = self::getServerURLForProfile($profile);

			// Now query the GServer for the platform name
			$gserver = DBA::selectFirst('gserver', ['platform'], ['nurl' => $server_url]);

			if (DBA::isResult($gserver) && !empty($gserver['platform'])) {
				$network_icon = $platform_icons[strtolower($gserver['platform'])] ?? $network_icon;
			}
		}

		return $network_icon;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function gender($current = "", $suffix = "")
	{
		$o = '';
		$select = [
			''                 => DI::l10n()->t('No answer'),
			'Male'             => DI::l10n()->t('Male'),
			'Female'           => DI::l10n()->t('Female'),
			'Currently Male'   => DI::l10n()->t('Currently Male'),
			'Currently Female' => DI::l10n()->t('Currently Female'),
			'Mostly Male'      => DI::l10n()->t('Mostly Male'),
			'Mostly Female'    => DI::l10n()->t('Mostly Female'),
			'Transgender'      => DI::l10n()->t('Transgender'),
			'Intersex'         => DI::l10n()->t('Intersex'),
			'Transsexual'      => DI::l10n()->t('Transsexual'),
			'Hermaphrodite'    => DI::l10n()->t('Hermaphrodite'),
			'Neuter'           => DI::l10n()->t('Neuter'),
			'Non-specific'     => DI::l10n()->t('Non-specific'),
			'Other'            => DI::l10n()->t('Other'),
			'Undecided'        => DI::l10n()->t('Undecided'),
		];

		Hook::callAll('gender_selector', $select);

		$o .= "<select name=\"gender$suffix\" id=\"gender-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 * @param string $suffix  optionsl, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function sexualPreference($current = "", $suffix = "")
	{
		$o = '';
		$select = [
			''              => DI::l10n()->t('No answer'),
			'Males'         => DI::l10n()->t('Males'),
			'Females'       => DI::l10n()->t('Females'),
			'Gay'           => DI::l10n()->t('Gay'),
			'Lesbian'       => DI::l10n()->t('Lesbian'),
			'No Preference' => DI::l10n()->t('No Preference'),
			'Bisexual'      => DI::l10n()->t('Bisexual'),
			'Autosexual'    => DI::l10n()->t('Autosexual'),
			'Abstinent'     => DI::l10n()->t('Abstinent'),
			'Virgin'        => DI::l10n()->t('Virgin'),
			'Deviant'       => DI::l10n()->t('Deviant'),
			'Fetish'        => DI::l10n()->t('Fetish'),
			'Oodles'        => DI::l10n()->t('Oodles'),
			'Nonsexual'     => DI::l10n()->t('Nonsexual'),
		];

		Hook::callAll('sexpref_selector', $select);

		$o .= "<select name=\"sexual$suffix\" id=\"sexual-select$suffix\" size=\"1\" >";
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}

	/**
	 * @param string $current optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function maritalStatus($current = "")
	{
		$o = '';
		$select = [
			''                     => DI::l10n()->t('No answer'),
			'Single'               => DI::l10n()->t('Single'),
			'Lonely'               => DI::l10n()->t('Lonely'),
			'In a relation'        => DI::l10n()->t('In a relation'),
			'Has crush'            => DI::l10n()->t('Has crush'),
			'Infatuated'           => DI::l10n()->t('Infatuated'),
			'Dating'               => DI::l10n()->t('Dating'),
			'Unfaithful'           => DI::l10n()->t('Unfaithful'),
			'Sex Addict'           => DI::l10n()->t('Sex Addict'),
			'Friends'              => DI::l10n()->t('Friends'),
			'Friends/Benefits'     => DI::l10n()->t('Friends/Benefits'),
			'Casual'               => DI::l10n()->t('Casual'),
			'Engaged'              => DI::l10n()->t('Engaged'),
			'Married'              => DI::l10n()->t('Married'),
			'Imaginarily married'  => DI::l10n()->t('Imaginarily married'),
			'Partners'             => DI::l10n()->t('Partners'),
			'Cohabiting'           => DI::l10n()->t('Cohabiting'),
			'Common law'           => DI::l10n()->t('Common law'),
			'Happy'                => DI::l10n()->t('Happy'),
			'Not looking'          => DI::l10n()->t('Not looking'),
			'Swinger'              => DI::l10n()->t('Swinger'),
			'Betrayed'             => DI::l10n()->t('Betrayed'),
			'Separated'            => DI::l10n()->t('Separated'),
			'Unstable'             => DI::l10n()->t('Unstable'),
			'Divorced'             => DI::l10n()->t('Divorced'),
			'Imaginarily divorced' => DI::l10n()->t('Imaginarily divorced'),
			'Widowed'              => DI::l10n()->t('Widowed'),
			'Uncertain'            => DI::l10n()->t('Uncertain'),
			'It\'s complicated'    => DI::l10n()->t('It\'s complicated'),
			'Don\'t care'          => DI::l10n()->t('Don\'t care'),
			'Ask me'               => DI::l10n()->t('Ask me'),
		];

		Hook::callAll('marital_selector', $select);

		$o .= '<select name="marital" id="marital-select" size="1" >';
		foreach ($select as $neutral => $selection) {
			if ($selection !== 'NOTRANSLATION') {
				$selected = (($neutral == $current) ? ' selected="selected" ' : '');
				$o .= "<option value=\"$neutral\" $selected >$selection</option>";
			}
		}
		$o .= '</select>';
		return $o;
	}
}
