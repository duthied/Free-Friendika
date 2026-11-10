<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Network\HTTPException;

/**
 * updates the profile image for the user (either a specified profile or the default profile)
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image
 */
class UpdateProfileImage extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// get mediadata from image or media (Twitter call api/account/update_profile_image provides image)
		if (!empty($_FILES['image'])) {
			$media = $_FILES['image'];
		} elseif (!empty($_FILES['media'])) {
			$media = $_FILES['media'];
		}

		// error if image data is missing
		if (empty($media)) {
			throw new HTTPException\BadRequestException('no media data submitted');
		}

		// save new profile image
		$resource_id = Photo::uploadAvatar($uid, $media);
		if (empty($resource_id)) {
			throw new HTTPException\InternalServerErrorException('image upload failed');
		}

		// output for client
		$skip_status = $this->getRequestValue($request, 'skip_status', false);

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info['verified']);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->addFormattedContent('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
