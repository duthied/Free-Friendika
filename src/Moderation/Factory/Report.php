<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Moderation\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Moderation\Collection;
use Friendica\Moderation\Entity;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class Report extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/** @var ClockInterface */
	private $clock;

	public function __construct(LoggerInterface $logger, ClockInterface $clock)
	{
		parent::__construct($logger);

		$this->clock = $clock;
	}

	/**
	 * @param array                        $row   `report` table row
	 * @param Collection\Report\Posts|null $posts List of posts attached to the report
	 * @param Collection\Report\Rules|null $rules List of rules from the terms of service, see System::getRules()
	 * @return Entity\Report
	 * @throws \Exception
	 */
	public function createFromTableRow(array $row, Collection\Report\Posts $posts = null, Collection\Report\Rules $rules = null): Entity\Report
	{
		return new Entity\Report(
			$row['reporter-id'],
			$row['cid'],
			$row['gsid'],
			new \DateTimeImmutable($row['created'], new \DateTimeZone('UTC')),
			$row['category-id'],
			$row['uid'],
			$row['comment'],
			$row['forward'],
			$posts ?? new Collection\Report\Posts(),
			$rules ?? new Collection\Report\Rules(),
			$row['public-remarks'],
			$row['private-remarks'],
			$row['edited'] ? new \DateTimeImmutable($row['edited'], new \DateTimeZone('UTC')) : null,
			$row['status'],
			$row['resolution'],
			$row['assigned-uid'],
			$row['last-editor-uid'],
			$row['id'],
		);
	}

	/**
	 * Creates a Report entity from a Mastodon API /reports request
	 *
	 * @param array  $rules      Line-number indexed node rules array, see System::getRules(true)
	 * @param int    $reporterId
	 * @param int    $cid
	 * @param int    $gsid
	 * @param string $comment
	 * @param string $category
	 * @param bool   $forward
	 * @param array  $postUriIds
	 * @param array  $ruleIds
	 * @param ?int   $uid
	 * @return Entity\Report
	 * @see \Friendica\Module\Api\Mastodon\Reports::post()
	 */
	public function createFromReportsRequest(array $rules, int $reporterId, int $cid, int $gsid, string $comment = '', string $category = '', bool $forward = false, array $postUriIds = [], array $ruleIds = [], int $uid = null): Entity\Report
	{
		if (count($ruleIds)) {
			$categoryId = Entity\Report::CATEGORY_VIOLATION;
		} elseif ($category == 'spam') {
			$categoryId = Entity\Report::CATEGORY_SPAM;
		} else {
			$categoryId = Entity\Report::CATEGORY_OTHER;
		}

		return new Entity\Report(
			$reporterId,
			$cid,
			$gsid,
			$this->clock->now(),
			$categoryId,
			$uid,
			$comment,
			$forward,
			new Collection\Report\Posts(array_map(function ($uriId) {
				return new Entity\Report\Post($uriId);
			}, $postUriIds)),
			new Collection\Report\Rules(array_map(function ($lineId) use ($rules) {
				return new Entity\Report\Rule($lineId, $rules[$lineId] ?? '');
			}, $ruleIds)),
		);
	}

	public function createFromForm(array $rules, int $cid, int $reporterId, int $categoryId, array $ruleIds, string $comment, array $uriIds, bool $forward): Entity\Report
	{
		$contact = Contact::getById($cid, ['gsid']);
		if (!$contact) {
			throw new \InvalidArgumentException('Contact with id: ' . $cid . ' not found');
		}

		if (!in_array($categoryId, Entity\Report::CATEGORIES)) {
			throw new \OutOfBoundsException('Category with id: ' . $categoryId . ' not found in set: [' . implode(', ', Entity\Report::CATEGORIES) . ']');
		}

		return new Entity\Report(
			Contact::getPublicIdByUserId($reporterId),
			$cid,
			$contact['gsid'],
			$this->clock->now(),
			$categoryId,
			$reporterId,
			$comment,
			$forward,
			new Collection\Report\Posts(array_map(function ($uriId) {
				return new Entity\Report\Post($uriId);
			}, $uriIds)),
			new Collection\Report\Rules(array_map(function ($lineId) use ($rules) {
				return new Entity\Report\Rule($lineId, $rules[$lineId] ?? '');
			}, $ruleIds)),
		);
	}
}
