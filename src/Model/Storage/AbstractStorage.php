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

namespace Friendica\Model\Storage;

use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

/**
 * A general storage class which loads common dependencies and implements common methods
 */
abstract class AbstractStorage implements IStorage
{
	/** @var L10n */
	protected $l10n;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param L10n            $l10n
	 * @param LoggerInterface $logger
	 */
	public function __construct(L10n $l10n, LoggerInterface $logger)
	{
		$this->l10n   = $l10n;
		$this->logger = $logger;
	}

	public function __toString()
	{
		return static::getName();
	}
}
