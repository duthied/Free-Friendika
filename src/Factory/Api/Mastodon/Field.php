<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Profile\ProfileField\Collection\ProfileFields;
use Friendica\Content\Text\BBCode;
use Friendica\Profile\ProfileField\Entity\ProfileField;
use Friendica\Network\HTTPException;

class Field extends BaseFactory
{
	/**
	 * @param ProfileField $profileField
	 *
	 * @return \Friendica\Object\Api\Mastodon\Field
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileField(ProfileField $profileField): \Friendica\Object\Api\Mastodon\Field
	{
		return new \Friendica\Object\Api\Mastodon\Field($profileField->label, BBCode::convertForUriId($profileField->uriId, $profileField->value, BBCode::ACTIVITYPUB));
	}

	/**
	 * @param ProfileFields $profileFields
	 *
	 * @return Fields
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileFields(ProfileFields $profileFields): Fields
	{
		$fields = [];

		foreach ($profileFields as $profileField) {
			$fields[] = $this->createFromProfileField($profileField);
		}

		return new Fields($fields);
	}
}
