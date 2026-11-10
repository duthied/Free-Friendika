<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util\Hooks\InstanceMocks;

interface IAmADecoratedInterface
{
	public function createSomething(string $aText, bool $cBool, string $bText): string;

	public function getAText(): ?string;

	public function getBText(): ?string;

	public function getCBool(): ?bool;
}
