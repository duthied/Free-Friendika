<?php

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
use Friendica\Model\Item;
use Friendica\Module\BaseNotifications;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Activity;
use Friendica\Repository\Notification;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Util\Temporal;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;

class NotificationFactory extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Notification */
	private $notification;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var string */
	private $nurl;

	public function __construct(LoggerInterface $logger, Database $dba, Notification $notification, BaseURL $baseUrl, L10n $l10n, App $app, IPConfig $pConfig, ISession $session)
	{
		parent::__construct($logger);

		$this->dba          = $dba;
		$this->notification = $notification;
		$this->baseUrl      = $baseUrl;
		$this->l10n         = $l10n;
		$this->nurl         = $app->contact['nurl'] ?? '';
	}

	/**
	 * Format the item query in an usable array
	 *
	 * @param array $item The item from the db query
	 *
	 * @return array The item, extended with the notification-specific information
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	private function formatItem(array $item)
	{
		$item['seen'] = ($item['unseen'] > 0 ? false : true);

		// For feed items we use the user's contact, since the avatar is mostly self choosen.
		if (!empty($item['network']) && $item['network'] == Protocol::FEED) {
			$item['author-avatar'] = $item['contact-avatar'];
		}

		$item['label'] = (($item['id'] == $item['parent']) ? 'post' : 'comment');
		$item['link']  = $this->baseUrl->get(true) . '/display/' . $item['parent-guid'];
		$item['image'] = Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO);
		$item['url']   = $item['author-link'];
		$item['text']  = (($item['id'] == $item['parent'])
			? $this->l10n->t("%s created a new post", $item['author-name'])
			: $this->l10n->t("%s commented on %s's post", $item['author-name'], $item['parent-author-name']));
		$item['when']  = DateTimeFormat::local($item['created'], 'r');
		$item['ago']   = Temporal::getRelativeDate($item['created']);

		return $item;
	}

	/**
	 * @param array $item
	 *
	 * @return \Friendica\Object\Notification\Notification
	 *
	 * @throws InternalServerErrorException
	 */
	private function createFromItem(array $item)
	{
		$item = $this->formatItem($item);

		// Transform the different types of notification in an usable array
		switch ($item['verb'] ?? '') {
			case Activity::LIKE:
				return new \Friendica\Object\Notification\Notification(
					'like',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s liked %s's post", $item['author-name'], $item['parent-author-name']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			case Activity::DISLIKE:
				return new \Friendica\Object\Notification\Notification(
					'dislike',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s disliked %s's post", $item['author-name'], $item['parent-author-name']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			case Activity::ATTEND:
				return new \Friendica\Object\Notification\Notification(
					'attend',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s is attending %s's event", $item['author-name'], $item['parent-author-name']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			case Activity::ATTENDNO:
				return new \Friendica\Object\Notification\Notification(
					'attendno',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s is not attending %s's event", $item['author-name'], $item['parent-author-name']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			case Activity::ATTENDMAYBE:
				return new \Friendica\Object\Notification\Notification(
					'attendmaybe',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s may attending %s's event", $item['author-name'], $item['parent-author-name']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			case Activity::FRIEND:
				if (!isset($item['object'])) {
					return new \Friendica\Object\Notification\Notification(
						'friend',
						$item['link'],
						$item['image'],
						$item['url'],
						$item['text'],
						$item['when'] ?? '',
						$item['ago'] ?? '',
						$item['seen'] ?? false);
				}

				$xmlHead       = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
				$obj           = XML::parseString($xmlHead . $item['object']);
				$item['fname'] = $obj->title;

				return new \Friendica\Object\Notification\Notification(
					'friend',
					$this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					Proxy::proxifyUrl($item['author-avatar'], false, Proxy::SIZE_MICRO),
					$item['author-link'],
					$this->l10n->t("%s is now friends with %s", $item['author-name'], $item['fname']),
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);

			default:
				return new \Friendica\Object\Notification\Notification(
					$item['label'],
					$item['link'],
					$item['image'],
					$item['url'],
					$item['text'],
					$item['when'] ?? '',
					$item['ago'] ?? '',
					$item['seen'] ?? false);
				break;
		}
	}

	/**
	 * Get system notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return \Friendica\Module\Notifications\Notification[]
	 */
	public function getSystemList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$conditions = ['uid' => local_user()];

		if (!$seen) {
			$conditions['seen'] = false;
		}

		$params          = [];
		$params['order'] = ['date' => 'DESC'];
		$params['limit'] = [$start, $limit];

		$formattedNotifications = [];
		try {
			$notifications = $this->notification->select($conditions, $params);

			foreach ($notifications as $notification) {
				$formattedNotifications[] = new \Friendica\Object\Notification\Notification(
					'notification',
					$this->baseUrl->get(true) . '/notification/view/' . $notification->id,
					Proxy::proxifyUrl($notification->photo, false, Proxy::SIZE_MICRO),
					$notification->url,
					strip_tags(BBCode::convert($notification->msg)),
					DateTimeFormat::local($notification->date, 'r'),
					Temporal::getRelativeDate($notification->date),
					$notification->seen);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $conditions, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get network notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getNetworkList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$conditions = ['wall' => false, 'uid' => local_user()];

		if (!$seen) {
			$conditions['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $conditions, $params);

			while ($item = $this->dba->fetch($items)) {
				$formattedNotifications[] = $this->createFromItem($item);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $conditions, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get personal notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getPersonalList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$myUrl    = str_replace('http://', '', $this->nurl);
		$diaspUrl = str_replace('/profile/', '/u/', $myUrl);

		$condition = ["NOT `wall` AND `uid` = ? AND (`item`.`author-id` = ? OR `item`.`tag` REGEXP ? OR `item`.`tag` REGEXP ?)",
			local_user(), public_contact(), $myUrl . '\\]', $diaspUrl . '\\]'];

		if (!$seen) {
			$condition[0] .= " AND `unseen`";
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $condition, $params);

			while ($item = $this->dba->fetch($items)) {
				$formattedNotifications[] = $this->createFromItem($item);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get home notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getHomeList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$condition = ['wall' => true, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $condition, $params);

			while ($item = $this->dba->fetch($items)) {
				$item = $this->formatItem($item);

				// Overwrite specific fields, not default item format
				$item['label'] = 'comment';
				$item['text']  = $this->l10n->t("%s commented on %s's post", $item['author-name'], $item['parent-author-name']);

				$formattedNotifications[] = $this->createFromItem($item);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}
}
