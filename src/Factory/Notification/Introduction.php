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

namespace Friendica\Factory\Notification;

use Exception;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Session\ISession;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Module\BaseNotifications;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\Notification;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating notification objects based on introductions
 * Currently, there are two main types of introduction based notifications:
 * - Friend suggestion
 * - Friend/Follower request
 */
class Introduction extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var IPConfig */
	private $pConfig;
	/** @var ISession */
	private $session;
	/** @var string */
	private $nick;

	public function __construct(LoggerInterface $logger, Database $dba, BaseURL $baseUrl, L10n $l10n, App $app, IPConfig $pConfig, ISession $session)
	{
		parent::__construct($logger);

		$this->dba          = $dba;
		$this->baseUrl      = $baseUrl;
		$this->l10n         = $l10n;
		$this->pConfig      = $pConfig;
		$this->session      = $session;
		$this->nick         = $app->user['nickname'] ?? '';
	}

	/**
	 * Get introductions
	 *
	 * @param bool $all     If false only include introductions into the query
	 *                      which aren't marked as ignored
	 * @param int  $start   Start the query at this point
	 * @param int  $limit   Maximum number of query results
	 * @param int  $id      When set, only the introduction with this id is displayed
	 *
	 * @return Notification\Introduction[]
	 */
	public function getList(bool $all = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT, int $id = 0)
	{
		$sql_extra     = "";

		if (empty($id)) {
			if (!$all) {
				$sql_extra = " AND NOT `ignore` ";
			}

			$sql_extra .= " AND NOT `intro`.`blocked` ";
		} else {
			$sql_extra = sprintf(" AND `intro`.`id` = %d ", intval($id));
		}

		$formattedNotifications = [];

		try {
			/// @todo Fetch contact details by "Contact::getDetailsByUrl" instead of queries to contact, fcontact and gcontact
			$stmtNotifications = $this->dba->p(
				"SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*,
				`fcontact`.`name` AS `fname`, `fcontact`.`url` AS `furl`, `fcontact`.`addr` AS `faddr`,
				`fcontact`.`photo` AS `fphoto`, `fcontact`.`request` AS `frequest`,
				`gcontact`.`location` AS `glocation`, `gcontact`.`about` AS `gabout`,
				`gcontact`.`keywords` AS `gkeywords`,
				`gcontact`.`network` AS `gnetwork`, `gcontact`.`addr` AS `gaddr`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = ? $sql_extra
			LIMIT ?, ?",
				$_SESSION['uid'],
				$start,
				$limit
			);

			while ($notification = $this->dba->fetch($stmtNotifications)) {
				// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
				// We have to distinguish between these two because they use different data.
				// Contact suggestions
				if ($notification['fid'] ?? '') {
					$return_addr = bin2hex($this->nick . '@' .
					                       $this->baseUrl->getHostName() .
					                       (($this->baseUrl->getURLPath()) ? '/' . $this->baseUrl->getURLPath() : ''));

					$formattedNotifications[] = new Notification\Introduction([
						'label'          => 'friend_suggestion',
						'str_type'       => $this->l10n->t('Friend Suggestion'),
						'intro_id'       => $notification['intro_id'],
						'madeby'         => $notification['name'],
						'madeby_url'     => $notification['url'],
						'madeby_zrl'     => Contact::magicLink($notification['url']),
						'madeby_addr'    => $notification['addr'],
						'contact_id'     => $notification['contact-id'],
						'photo'          => (!empty($notification['fphoto']) ? Proxy::proxifyUrl($notification['fphoto'], false, Proxy::SIZE_SMALL) : "images/person-300.jpg"),
						'name'           => $notification['fname'],
						'url'            => $notification['furl'],
						'zrl'            => Contact::magicLink($notification['furl']),
						'hidden'         => $notification['hidden'] == 1,
						'post_newfriend' => (intval($this->pConfig->get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
						'note'           => $notification['note'],
						'request'        => $notification['frequest'] . '?addr=' . $return_addr]);

					// Normal connection requests
				} else {
					$notification = $this->getMissingData($notification);

					if (empty($notification['url'])) {
						continue;
					}

					// Don't show these data until you are connected. Diaspora is doing the same.
					if ($notification['gnetwork'] === Protocol::DIASPORA) {
						$notification['glocation'] = "";
						$notification['gabout']    = "";
					}

					$formattedNotifications[] = new Notification\Introduction([
						'label'          => (($notification['network'] !== Protocol::OSTATUS) ? 'friend_request' : 'follower'),
						'str_type'       => (($notification['network'] !== Protocol::OSTATUS) ? $this->l10n->t('Friend/Connect Request') : $this->l10n->t('New Follower')),
						'dfrn_id'        => $notification['issued-id'],
						'uid'            => $this->session->get('uid'),
						'intro_id'       => $notification['intro_id'],
						'contact_id'     => $notification['contact-id'],
						'photo'          => (!empty($notification['photo']) ? Proxy::proxifyUrl($notification['photo'], false, Proxy::SIZE_SMALL) : "images/person-300.jpg"),
						'name'           => $notification['name'],
						'location'       => BBCode::convert($notification['glocation'], false),
						'about'          => BBCode::convert($notification['gabout'], false),
						'keywords'       => $notification['gkeywords'],
						'hidden'         => $notification['hidden'] == 1,
						'post_newfriend' => (intval($this->pConfig->get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
						'url'            => $notification['url'],
						'zrl'            => Contact::magicLink($notification['url']),
						'addr'           => $notification['gaddr'],
						'network'        => $notification['gnetwork'],
						'knowyou'        => $notification['knowyou'],
						'note'           => $notification['note'],
					]);
				}
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['uid' => $_SESSION['uid'], 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Check for missing contact data and try to fetch the data from
	 * from other sources
	 *
	 * @param array $intro The input array with the intro data
	 *
	 * @return array The array with the intro data
	 *
	 * @throws InternalServerErrorException
	 */
	private function getMissingData(array $intro)
	{
		// If the network and the addr isn't available from the gcontact
		// table entry, take the one of the contact table entry
		if (empty($intro['gnetwork']) && !empty($intro['network'])) {
			$intro['gnetwork'] = $intro['network'];
		}
		if (empty($intro['gaddr']) && !empty($intro['addr'])) {
			$intro['gaddr'] = $intro['addr'];
		}

		// If the network and addr is still not available
		// get the missing data data from other sources
		if (empty($intro['gnetwork']) || empty($intro['gaddr'])) {
			$ret = Contact::getDetailsByURL($intro['url']);

			if (empty($intro['gnetwork']) && !empty($ret['network'])) {
				$intro['gnetwork'] = $ret['network'];
			}
			if (empty($intro['gaddr']) && !empty($ret['addr'])) {
				$intro['gaddr'] = $ret['addr'];
			}
		}

		return $intro;
	}
}
