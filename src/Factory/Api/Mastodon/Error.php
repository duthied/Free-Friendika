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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

/** @todo A Factory shouldn't return something to the frontpage, it's for creating content, not showing it */
class Error extends BaseFactory
{
	/** @var L10n */
	private $l10n;

	public function __construct(LoggerInterface $logger, L10n $l10n)
	{
		parent::__construct($logger);
		$this->l10n   = $l10n;
	}

	public function RecordNotFound(): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $this->l10n->t('Record not found');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function UnprocessableEntity(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Unprocessable Entity');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function Unauthorized(string $error = '', string $error_description = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Unauthorized');
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function Forbidden(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Token is not authorized with a valid user or is missing a required scope');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}

	public function InternalError(string $error = ''): \Friendica\Object\Api\Mastodon\Error
	{
		$error             = $error ?: $this->l10n->t('Internal Server Error');
		$error_description = '';
		return new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
	}
}
