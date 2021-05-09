<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Model\Storage;

/**
 * Interface for storage backends
 */
interface IStorage
{
	/**
	 * Get data from backend
	 *
	 * @param string $reference Data reference
	 *
	 * @return string
	 */
	public function get(string $reference);

	/**
	 * Put data in backend as $ref. If $ref is not defined a new reference is created.
	 *
	 * @param string $data      Data to save
	 * @param string $reference Data reference. Optional.
	 *
	 * @return string Saved data reference
	 */
	public function put(string $data, string $reference = "");

	/**
	 * Remove data from backend
	 *
	 * @param string $reference Data reference
	 *
	 * @return boolean  True on success
	 */
	public function delete(string $reference);

	/**
	 * Get info about storage options
	 *
	 * @return array
	 *
	 * This method return an array with informations about storage options
	 * from which the form presented to the user is build.
	 *
	 * The returned array is:
	 *
	 *    [
	 *      'option1name' => [ ..info.. ],
	 *      'option2name' => [ ..info.. ],
	 *      ...
	 *    ]
	 *
	 * An empty array can be returned if backend doesn't have any options
	 *
	 * The info array for each option MUST be as follows:
	 *
	 *    [
	 *      'type',      // define the field used in form, and the type of data.
	 *                   // one of 'checkbox', 'combobox', 'custom', 'datetime',
	 *                   // 'input', 'intcheckbox', 'password', 'radio', 'richtext'
	 *                   // 'select', 'select_raw', 'textarea'
	 *
	 *      'label',     // Translatable label of the field
	 *      'value',     // Current value
	 *      'help text', // Translatable description for the field
	 *      extra data   // Optional. Depends on 'type':
	 *                   // select: array [ value => label ] of choices
	 *                   // intcheckbox: value of input element
	 *                   // select_raw: prebuild html string of < option > tags
	 *    ]
	 *
	 * See https://github.com/friendica/friendica/wiki/Quick-Template-Guide
	 */
	public function getOptions();

	/**
	 * Validate and save options
	 *
	 * @param array $data Array [optionname => value] to be saved
	 *
	 * @return array  Validation errors: [optionname => error message]
	 *
	 * Return array must be empty if no error.
	 */
	public function saveOptions(array $data);

	/**
	 * The name of the backend
	 *
	 * @return string
	 */
	public function __toString();

	/**
	 * The name of the backend
	 *
	 * @return string
	 */
	public static function getName();
}
