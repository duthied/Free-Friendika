<?php

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\System;

/**
 * Standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo extends BaseModule
{
	public static function init(array $parameters = [])
	{
		$config = self::getApp()->getConfig();

		if (!$config->get('system', 'nodeinfo')) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}
	}

	public static function rawContent(array $parameters = [])
	{
		$app = self::getApp();

		// @TODO: Replace with parameter from router
		// if the first argument is ".well-known", print the well-known text
		if (($app->argc > 1) && ($app->argv[0] == '.well-known')) {
			self::printWellKnown($app);
		// otherwise print the nodeinfo
		} else {
			self::printNodeInfo($app);
		}
	}

	/**
	 * Prints the well-known nodeinfo redirect
	 *
	 * @param App $app
	 *
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	private static function printWellKnown(App $app)
	{
		$config = $app->getConfig();

		if (!$config->get('system', 'nodeinfo')) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$nodeinfo = [
			'links' => [[
				'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
				'href' => $app->getBaseURL() . '/nodeinfo/1.0']]
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}

	/**
	 * Print the nodeinfo
	 *
	 * @param App $app
	 */
	private static function printNodeInfo(App $app)
	{
		$config = $app->getConfig();

		$smtp = (function_exists('imap_open') && !$config->get('system', 'imap_disabled') && !$config->get('system', 'dfrn_only'));

		$nodeinfo = [
			'version'           => 1.0,
			'software'          => [
				'name'    => 'friendica',
				'version' => FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION,
			],
			'protocols'         => [
				'inbound'  => [
					'friendica',
				],
				'outbound' => [
					'friendica',
				],
			],
			'services'          => [
				'inbound'  => [],
				'outbound' => [],
			],
			'usage'             => [],
			'openRegistrations' => intval($config->get('config', 'register_policy')) !== Register::CLOSED,
			'metadata'          => [
				'nodeName' => $config->get('config', 'sitename'),
			],
		];

		if (!empty($config->get('system', 'diaspora_enabled'))) {
			$nodeinfo['protocols']['inbound'][] = 'diaspora';
			$nodeinfo['protocols']['outbound'][] = 'diaspora';
		}

		if (empty($config->get('system', 'ostatus_disabled'))) {
			$nodeinfo['protocols']['inbound'][] = 'gnusocial';
			$nodeinfo['protocols']['outbound'][] = 'gnusocial';
		}

		if (!empty($config->get('system', 'nodeinfo'))) {

			$nodeinfo['usage']['users'] = [
				'total'          => intval($config->get('nodeinfo', 'total_users')),
				'activeHalfyear' => intval($config->get('nodeinfo', 'active_users_halfyear')),
				'activeMonth'    => intval($config->get('nodeinfo', 'active_users_monthly'))
			];
			$nodeinfo['usage']['localPosts'] = intval($config->get('nodeinfo', 'local_posts'));
			$nodeinfo['usage']['localComments'] = intval($config->get('nodeinfo', 'local_comments'));

			if (Addon::isEnabled('blogger')) {
				$nodeinfo['services']['outbound'][] = 'blogger';
			}
			if (Addon::isEnabled('dwpost')) {
				$nodeinfo['services']['outbound'][] = 'dreamwidth';
			}
			if (Addon::isEnabled('statusnet')) {
				$nodeinfo['services']['inbound'][] = 'gnusocial';
				$nodeinfo['services']['outbound'][] = 'gnusocial';
			}
			if (Addon::isEnabled('ijpost')) {
				$nodeinfo['services']['outbound'][] = 'insanejournal';
			}
			if (Addon::isEnabled('libertree')) {
				$nodeinfo['services']['outbound'][] = 'libertree';
			}
			if (Addon::isEnabled('buffer')) {
				$nodeinfo['services']['outbound'][] = 'linkedin';
			}
			if (Addon::isEnabled('ljpost')) {
				$nodeinfo['services']['outbound'][] = 'livejournal';
			}
			if (Addon::isEnabled('buffer')) {
				$nodeinfo['services']['outbound'][] = 'pinterest';
			}
			if (Addon::isEnabled('posterous')) {
				$nodeinfo['services']['outbound'][] = 'posterous';
			}
			if (Addon::isEnabled('pumpio')) {
				$nodeinfo['services']['inbound'][] = 'pumpio';
				$nodeinfo['services']['outbound'][] = 'pumpio';
			}

			if ($smtp) {
				$nodeinfo['services']['outbound'][] = 'smtp';
			}
			if (Addon::isEnabled('tumblr')) {
				$nodeinfo['services']['outbound'][] = 'tumblr';
			}
			if (Addon::isEnabled('twitter') || Addon::isEnabled('buffer')) {
				$nodeinfo['services']['outbound'][] = 'twitter';
			}
			if (Addon::isEnabled('wppost')) {
				$nodeinfo['services']['outbound'][] = 'wordpress';
			}
			$nodeinfo['metadata']['protocols'] = $nodeinfo['protocols'];
			$nodeinfo['metadata']['protocols']['outbound'][] = 'atom1.0';
			$nodeinfo['metadata']['protocols']['inbound'][] = 'atom1.0';
			$nodeinfo['metadata']['protocols']['inbound'][] = 'rss2.0';

			$nodeinfo['metadata']['services'] = $nodeinfo['services'];

			if (Addon::isEnabled('twitter')) {
				$nodeinfo['metadata']['services']['inbound'][] = 'twitter';
			}

			$nodeinfo['metadata']['explicitContent'] = $config->get('system', 'explicit_content', false) == true;
		}

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
