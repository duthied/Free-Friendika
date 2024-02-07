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

namespace Friendica\Moderation\Entity;

use Friendica\Moderation\Collection;

/**
 * @property-read int                     $id
 * @property-read int                     $reporterCid
 * @property-read int                     $cid
 * @property-read int                     $gsid
 * @property-read string                  $comment
 * @property-read string                  $publicRemarks
 * @property-read string                  $privateRemarks
 * @property-read bool                    $forward
 * @property-read int                     $category
 * @property-read int                     $status
 * @property-read int|null                $resolution
 * @property-read int                     $reporterUid
 * @property-read int|null                $lastEditorUid
 * @property-read int|null                $assignedUid
 * @property-read \DateTimeImmutable      $created
 * @property-read \DateTimeImmutable|null $edited
 * @property-read Collection\Report\Posts $posts
 * @property-read Collection\Report\Rules $rules
 */
final class Report extends \Friendica\BaseEntity
{
	const CATEGORY_OTHER = 1;
	const CATEGORY_SPAM = 2;
	const CATEGORY_ILLEGAL = 4;
	const CATEGORY_SAFETY = 8;
	const CATEGORY_UNWANTED = 16;
	const CATEGORY_VIOLATION = 32;

	const CATEGORIES  = [
		self::CATEGORY_OTHER,
		self::CATEGORY_SPAM,
		self::CATEGORY_ILLEGAL,
		self::CATEGORY_SAFETY,
		self::CATEGORY_UNWANTED,
		self::CATEGORY_VIOLATION,
	];

	const STATUS_CLOSED = 0;
	const STATUS_OPEN = 1;

	const RESOLUTION_ACCEPTED = 0;
	const RESOLUTION_REJECTED = 1;

	/** @var int|null */
	protected $id;
	/** @var int ID of the contact making a moderation report */
	protected $reporterCid;
	/** @var int ID of the contact being reported */
	protected $cid;
	/** @var int ID of the gserver of the contact being reported */
	protected $gsid;
	/** @var string Reporter comment */
	protected $comment;
	/** @var int One of CATEGORY_* */
	protected $category;
	/** @var int ID of the user making a moderation report, null in case of an incoming forwarded report */
	protected $reporterUid;
	/** @var bool Whether this report should be forwarded to the remote server */
	protected $forward;
	/** @var \DateTimeImmutable When the report was created */
	protected $created;
	/** @var Collection\Report\Rules List of terms of service rule lines being possibly violated */
	protected $rules;
	/** @var Collection\Report\Posts List of URI IDs of posts supporting the report */
	protected $posts;
	/** @var string Remarks shared with the reporter */
	protected $publicRemarks;
	/** @var string Remarks shared with the moderation team */
	protected $privateRemarks;
	/** @var \DateTimeImmutable|null When the report was last edited */
	protected $edited;
	/** @var int One of STATUS_* */
	protected $status;
	/** @var int|null One of RESOLUTION_* if any */
	protected $resolution;
	/** @var int|null Assigned moderator user id if any */
	protected $assignedUid;
	/** @var int|null Last editor user ID if any */
	protected $lastEditorUid;

	public function __construct(
		int $reporterCid,
		int $cid,
		int $gsid,
		\DateTimeImmutable $created,
		int $category,
		int $reporterUid = null,
		string $comment = '',
		bool $forward = false,
		Collection\Report\Posts $posts = null,
		Collection\Report\Rules $rules = null,
		string $publicRemarks = '',
		string $privateRemarks = '',
		\DateTimeImmutable $edited = null,
		int $status = self::STATUS_OPEN,
		int $resolution = null,
		int $assignedUid = null,
		int $lastEditorUid = null,
		int $id = null
	) {
		$this->reporterCid    = $reporterCid;
		$this->cid            = $cid;
		$this->gsid           = $gsid;
		$this->created        = $created;
		$this->category       = $category;
		$this->reporterUid    = $reporterUid;
		$this->comment        = $comment;
		$this->forward        = $forward;
		$this->posts          = $posts ?? new Collection\Report\Posts();
		$this->rules          = $rules ?? new Collection\Report\Rules();
		$this->publicRemarks  = $publicRemarks;
		$this->privateRemarks = $privateRemarks;
		$this->edited         = $edited;
		$this->status         = $status;
		$this->resolution     = $resolution;
		$this->assignedUid    = $assignedUid;
		$this->lastEditorUid  = $lastEditorUid;
		$this->id             = $id;
	}
}
