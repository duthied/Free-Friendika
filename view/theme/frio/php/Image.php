<?php
/**
 * @file view/theme/frio/php/Image.php
 * @brief contain methods to deal with images
 */

/**
 * @brief This class contains methods to deal with images
 */
use Friendica\Core\L10n;

class Image
{
	/**
	 * @brief Give all available options for the background image
	 *
	 * @param array $arr Array with the present user settings
	 *
	 * @return array Array with the immage options
	 */
	public static function get_options($arr)
	{
		$bg_image_options = [
			'stretch' => ['frio_bg_image_option', L10n::t("Stretch"), "stretch", L10n::t("Resize image to the width of the screen and show background color below on long pages."), ($arr["bg_image_option"] == "stretch")],
			'cover'   => ['frio_bg_image_option', L10n::t("Resize, fill and clip"), "cover", L10n::t("Resize image to fill entire screen, clipping either the right or the bottom."), ($arr["bg_image_option"] == "cover")],
			'repeat'  => ['frio_bg_image_option', L10n::t("Repeat"), "repeat", L10n::t("Repeat image to fill the screen."), ($arr["bg_image_option"] == "repeat")],
			'contain' => ['frio_bg_image_option', L10n::t("Repeat one row"), "contain", L10n::t("Resize image to repeat it on a single row, either vertical or horizontal."), ($arr["bg_image_option"] == "contain")],
		];

		return $bg_image_options;
	}
}
