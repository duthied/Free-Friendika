<?php

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Fields;
use Friendica\Collection\ProfileFields;
use Friendica\Content\Text\BBCode;
use Friendica\Model\ProfileField;
use Friendica\Network\HTTPException;

class Field extends BaseFactory
{
	/**
	 * @param ProfileField $profileField
	 * @return \Friendica\Api\Entity\Mastodon\Field
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileField(ProfileField $profileField)
	{
		return new \Friendica\Api\Entity\Mastodon\Field($profileField->label, BBCode::convert($profileField->value, false, 9));
	}

	/**
	 * @param ProfileFields $profileFields
	 * @return Fields
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileFields(ProfileFields $profileFields)
	{
		$fields = [];

		foreach ($profileFields as $profileField) {
			$fields[] = $this->createFromProfileField($profileField);
		}

		return new Fields($fields);
	}
}
