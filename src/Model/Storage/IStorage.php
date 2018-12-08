<?php
/**
 * @file src/Model/Storage/IStorage.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

/**
 * @brief Interface for storage backends
 */
interface IStorage
{
	/**
	 * @brief Get data from backend
	 * @param string  $ref  Data reference
	 * @return string
     */
	public static function get($ref);

	/**
	 * @brief Put data in backend as $ref. If $ref is not defined a new reference is created.
	 * @param string  $data  Data to save
	 * @param string  $ref   Data referece. Optional.
	 * @return string Saved data referece
	 */
	public static function put($data, $ref = "");

	/**
	 * @brief Remove data from backend
	 * @param string  $ref  Data referece
	 * @return boolean  True on success
	 */
	public static function delete($ref);
	
	/**
	 * @brief Get info about storage options
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
	 *                   // 'select', 'select_raw', 'textarea', 'yesno'
	 *
	 *      'label',     // Translatable label of the field
	 *      'value',     // Current value
	 *      'help text', // Translatable description for the field
	 *      extra data   // Optional. Depends on 'type':
	 *                   // select: array [ value => label ] of choices
	 *                   // intcheckbox: value of input element
	 *                   // select_raw: prebuild html string of < option > tags
	 *                   // yesno: array [ 'label no', 'label yes']
	 *    ]
	 *
	 * See https://github.com/friendica/friendica/wiki/Quick-Template-Guide
	 */
	public static function getOptions();
	
	/**
	 * @brief Validate and save options
	 *
	 * @param array  $data  Array [optionname => value] to be saved
	 *
	 * @return array  Validation errors: [optionname => error message]
	 *
	 * Return array must be empty if no error.
	 */
	public static function saveOptions($data);
	
}


