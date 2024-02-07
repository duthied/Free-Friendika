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

namespace Friendica\Content;

use Friendica\Content\Image\Collection\MasonryImageRow;
use Friendica\Content\Image\Entity\MasonryImage;
use Friendica\Content\Post\Collection\PostMedias;
use Friendica\Core\Renderer;

class Image
{
	public static function getBodyAttachHtml(PostMedias $PostMediaImages): string
	{
		$media = '';

		if ($PostMediaImages->haveDimensions()) {
			if (count($PostMediaImages) > 1) {
				$media = self::getHorizontalMasonryHtml($PostMediaImages);
			} elseif (count($PostMediaImages) == 1) {
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/image/single_with_height_allocation.tpl'), [
					'$image' => $PostMediaImages[0],
					'$allocated_height' => $PostMediaImages[0]->getAllocatedHeight(),
					'$allocated_max_width' => ($PostMediaImages[0]->previewWidth ?? $PostMediaImages[0]->width) . 'px',
				]);
			}
		} else {
			if (count($PostMediaImages) > 1) {
				$media = self::getImageGridHtml($PostMediaImages);
			} elseif (count($PostMediaImages) == 1) {
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/image/single.tpl'), [
					'$image' => $PostMediaImages[0],
				]);
			}
		}

		return $media;
	}

	/**
	 * @param PostMedias $images
	 * @return string
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 */
	private static function getImageGridHtml(PostMedias $images): string
	{
		// Image for first column (fc) and second column (sc)
		$images_fc = [];
		$images_sc = [];

		for ($i = 0; $i < count($images); $i++) {
			($i % 2 == 0) ? ($images_fc[] = $images[$i]) : ($images_sc[] = $images[$i]);
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/image/grid.tpl'), [
			'columns' => [
				'fc' => $images_fc,
				'sc' => $images_sc,
			],
		]);
	}

	/**
	 * Creates a horizontally masoned gallery with a fixed maximum number of pictures per row.
	 *
	 * For each row, we calculate how much of the total width each picture will take depending on their aspect ratio
	 * and how much relative height it needs to accomodate all pictures next to each other with their height normalized.
	 *
	 * @param array $images
	 * @return string
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 */
	private static function getHorizontalMasonryHtml(PostMedias $images): string
	{
		static $column_size = 2;

		$rows = array_map(
			function (PostMedias $PostMediaImages) {
				if ($singleImageInRow = count($PostMediaImages) == 1) {
					$PostMediaImages[] = $PostMediaImages[0];
				}

				$widths = [];
				$heights = [];
				foreach ($PostMediaImages as $PostMediaImage) {
					if ($PostMediaImage->width && $PostMediaImage->height) {
						$widths[] = $PostMediaImage->width;
						$heights[] = $PostMediaImage->height;
					} else {
						$widths[] = $PostMediaImage->previewWidth;
						$heights[] = $PostMediaImage->previewHeight;
					}
				}

				$maxHeight = max($heights);

				// Corrected width preserving aspect ratio when all images on a row are the same height
				$correctedWidths = [];
				foreach ($widths as $i => $width) {
					$correctedWidths[] = $width * $maxHeight / $heights[$i];
				}

				$totalWidth = array_sum($correctedWidths);

				$row_images2 = [];

				if ($singleImageInRow) {
					unset($PostMediaImages[1]);
				}

				foreach ($PostMediaImages as $i => $PostMediaImage) {
					$row_images2[] = new MasonryImage(
						$PostMediaImage->uriId,
						$PostMediaImage->url,
						$PostMediaImage->preview,
						$PostMediaImage->description ?? '',
						100 * $correctedWidths[$i] / $totalWidth,
						100 * $maxHeight / $correctedWidths[$i]
					);
				}

				// This magic value will stay constant for each image of any given row and is ultimately
				// used to determine the height of the row container relative to the available width.
				$commonHeightRatio = 100 * $correctedWidths[0] / $totalWidth / ($widths[0] / $heights[0]);

				return new MasonryImageRow($row_images2, count($row_images2), $commonHeightRatio);
			},
			$images->chunk($column_size)
		);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/image/horizontal_masonry.tpl'), [
			'$rows' => $rows,
			'$column_size' => $column_size,
		]);
	}
}
