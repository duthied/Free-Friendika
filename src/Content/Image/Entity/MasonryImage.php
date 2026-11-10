<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Image\Entity;

use Friendica\BaseEntity;
use Psr\Http\Message\UriInterface;

/**
 * @property-read int $uriId
 * @property-read UriInterface $url
 * @property-read ?UriInterface $preview
 * @property-read string $description
 * @property-read float $heightRatio
 * @property-read float $widthRatio
 * @see \Friendica\Content\Image::getHorizontalMasonryHtml()
 */
class MasonryImage extends BaseEntity
{
	/** @var int */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var ?UriInterface */
	protected $preview;
	/** @var string */
	protected $description;
	/** @var float Ratio of the width of the image relative to the total width of the images on the row */
	protected $widthRatio;
	/** @var float Ratio of the height of the image relative to its width for height allocation */
	protected $heightRatio;

	public function __construct(int $uriId, UriInterface $url, ?UriInterface $preview, string $description, float $widthRatio, float $heightRatio)
	{
		$this->url         = $url;
		$this->uriId       = $uriId;
		$this->preview     = $preview;
		$this->description = $description;
		$this->widthRatio  = $widthRatio;
		$this->heightRatio = $heightRatio;
	}
}
