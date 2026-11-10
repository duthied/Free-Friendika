<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\User\Settings\Collection;

class UserGServers extends \Friendica\BaseCollection
{
	public function current(): \Friendica\User\Settings\Entity\UserGServer
	{
		return parent::current();
	}
}
