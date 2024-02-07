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

class FakeInstance implements IAmADecoratedInterface
{
	protected $aText = null;
	protected $cBool = null;
	protected $bText = null;

	public function __construct(string $aText = null, bool $cBool = null, string $bText = null)
	{
		$this->aText = $aText;
		$this->cBool = $cBool;
		$this->bText = $bText;
	}

	public function createSomething(string $aText, bool $cBool, string $bText): string
	{
		$this->aText = $aText;
		$this->cBool = $cBool;
		$this->bText = $bText;

		return '';
	}

	public function getAText(): ?string
	{
		return $this->aText;
	}

	public function getBText(): ?string
	{
		return $this->bText;
	}

	public function getCBool(): ?bool
	{
		return $this->cBool;
	}
}
