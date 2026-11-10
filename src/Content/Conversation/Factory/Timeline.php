<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

class Timeline extends \Friendica\BaseFactory
{
	/** @var L10n */
	protected $l10n;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var UserDefinedChannel */
	protected $channelRepository;

	public function __construct(UserDefinedChannel $channel, L10n $l10n, LoggerInterface $logger, IManageConfigValues $config)
	{
		parent::__construct($logger);

		$this->channelRepository = $channel;
		$this->l10n              = $l10n;
		$this->config            = $config;
	}
}
