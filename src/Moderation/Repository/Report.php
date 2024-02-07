<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Moderation\Repository;

use Friendica\BaseEntity;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Model\Post;
use Friendica\Moderation\Factory;
use Friendica\Moderation\Collection;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

final class Report extends \Friendica\BaseRepository
{
	protected static $table_name = 'report';

	/** @var Factory\Report */
	protected $factory;
	/** @var Factory\Report\Post */
	protected $postFactory;
	/** @var Factory\Report\Rule */
	protected $ruleFactory;

	public function __construct(Database $database, LoggerInterface $logger, Factory\Report $factory, Factory\Report\Post $postFactory, Factory\Report\Rule $ruleFactory)
	{
		parent::__construct($database, $logger, $factory);

		$this->factory     = $factory;
		$this->postFactory = $postFactory;
		$this->ruleFactory = $ruleFactory;
	}

	public function selectOneById(int $lastInsertId): \Friendica\Moderation\Entity\Report
	{
		return $this->_selectOne(['id' => $lastInsertId]);
	}

	public function save(\Friendica\Moderation\Entity\Report $Report): \Friendica\Moderation\Entity\Report
	{
		$fields = [
			'reporter-id'     => $Report->reporterCid,
			'uid'             => $Report->reporterUid,
			'cid'             => $Report->cid,
			'gsid'            => $Report->gsid,
			'comment'         => $Report->comment,
			'forward'         => $Report->forward,
			'category-id'     => $Report->category,
			'public-remarks'  => $Report->publicRemarks,
			'private-remarks' => $Report->privateRemarks,
			'last-editor-uid' => $Report->lastEditorUid,
			'assigned-uid'    => $Report->assignedUid,
			'status'          => $Report->status,
			'resolution'      => $Report->resolution,
			'created'         => $Report->created->format(DateTimeFormat::MYSQL),
			'edited'          => $Report->edited ? $Report->edited->format(DateTimeFormat::MYSQL) : null,
		];

		if ($Report->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Report->id]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newReportId = $this->db->lastInsertId();

			foreach ($Report->posts as $post) {
				if (Post::exists(['uri-id' => $post->uriId])) {
					$this->db->insert('report-post', ['rid' => $newReportId, 'uri-id' => $post->uriId, 'status' => $post->status]);
				} else {
					Logger::notice('Post does not exist', ['uri-id' => $post->uriId, 'report' => $Report]);
				}
			}

			foreach ($Report->rules as $rule) {
				$this->db->insert('report-rule', ['rid' => $newReportId, 'line-id' => $rule->lineId, 'text' => $rule->text]);
			}

			$Report = $this->selectOneById($newReportId);
		}

		return $Report;
	}

	protected function _selectOne(array $condition, array $params = []): BaseEntity
	{
		$fields = $this->db->selectFirst(self::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		$reportPosts = new Collection\Report\Posts(array_map([$this->postFactory, 'createFromTableRow'], $this->db->selectToArray('report-post', ['uri-id', 'status'], ['rid' => $condition['id'] ?? 0])));
		$reportRules = new Collection\Report\Rules(array_map([$this->ruleFactory, 'createFromTableRow'], $this->db->selectToArray('report-rule', ['line-id', 'text'], ['rid' => $condition['id'] ?? 0])));

		return $this->factory->createFromTableRow($fields, $reportPosts, $reportRules);
	}
}
