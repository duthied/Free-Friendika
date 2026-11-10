<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class MediaAttachmentsConfig
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class MediaAttachmentsConfig extends BaseDataTransferObject
{
	/** @var string[] */
	protected $supported_mime_types;
	/** @var int */
	protected $image_size_limit;
	/** @var int */
	protected $image_matrix_limit;
	/** @var int */
	protected $video_size_limit = 0;
	/** @var int */
	protected $video_frame_rate_limit = 60;
	/** @var int */
	protected $video_matrix_limit = 0;

	/**
	 * @param array $supported_mime_types
	 */
	public function __construct(array $supported_mime_types, int $image_size_limit, int $image_matrix_limit, int $media_size_limit)
	{
		$this->supported_mime_types = $supported_mime_types;
		$this->image_size_limit     = $image_size_limit;
		$this->image_matrix_limit   = $image_matrix_limit;
		$this->video_size_limit     = $media_size_limit;
		$this->video_matrix_limit   = $image_matrix_limit;
	}
}
