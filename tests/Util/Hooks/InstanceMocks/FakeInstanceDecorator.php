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

namespace Friendica\Test\Util\Hooks\InstanceMocks;

class FakeInstanceDecorator implements IAmADecoratedInterface
{
	public static $countInstance = 0;

	const PREFIX = 'prefix1';

	/** @var IAmADecoratedInterface */
	protected $orig;

	public function __construct(IAmADecoratedInterface $orig)
	{
		$this->orig   = $orig;

		self::$countInstance++;
	}

	public function createSomething(string $aText, bool $cBool, string $bText): string
	{
		return $this->orig->createSomething($aText, $cBool, $bText);
	}

	public function getAText(): ?string
	{
		return static::PREFIX . $this->orig->getAText();
	}

	public function getBText(): ?string
	{
		return static::PREFIX . $this->orig->getBText();
	}

	public function getCBool(): ?bool
	{
		return static::PREFIX . $this->orig->getCBool();
	}
}
