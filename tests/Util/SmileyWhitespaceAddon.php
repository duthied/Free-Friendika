<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\Content\Smilies;

function add_test_unicode_smilies(array &$b)
{
	// String-substitution smilies
	// - no whitespaces
	Smilies::add($b, '⽕', '&#x1F525;');
	// - with whitespaces
	Smilies::add($b, ':hugging face:', '&#x1F917;');
	// - with multiple whitespaces
	Smilies::add($b, ':face with hand over mouth:', '&#x1F92D;');
	// Image-based smilies
	// - with whitespaces
	Smilies::add($b, ':smiley heart 333:', '<img class="smiley" src="/images/smiley-heart.gif" alt="smiley-heart" title="smiley-heart" />');
}
