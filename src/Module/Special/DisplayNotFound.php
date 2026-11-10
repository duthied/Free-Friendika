<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Special;

use Friendica\Core\Renderer;

/**
 * This is a special case of the HTTPException module where the message is intended to be HTML.
 * This module should be called directly from the Display module and shouldn't be routed to.
 */
class DisplayNotFound extends \Friendica\BaseModule
{
	protected function content(array $request = []): string
	{
		$reasons = [
			$this->t("The top-level post isn't visible."),
			$this->t('The top-level post was deleted.'),
			$this->t('This node has blocked the top-level author or the author of the shared post.'),
			$this->t('You have ignored or blocked the top-level author or the author of the shared post.'),
			$this->t("You have ignored the top-level author's server or the shared post author's server."),
		];

		$tpl = Renderer::getMarkupTemplate('special/displaynotfound.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'   => $this->t('Conversation Not Found'),
				'desc1'   => $this->t("Unfortunately, the requested conversation isn't available to you."),
				'desc2'   => $this->t('Possible reasons include:'),
				'reasons' => $reasons,
			],
		]);
	}
}
