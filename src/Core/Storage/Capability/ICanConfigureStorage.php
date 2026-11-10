<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Capability;

/**
 * The interface to use for configurable storage backends
 */
interface ICanConfigureStorage
{
	/**
	 * Get info about storage options
	 *
	 * @return array
	 *
	 * This method return an array with information about storage options
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
	public function getOptions(): array;

	/**
	 * Validate and save options
	 *
	 * @param array $data Array [optionname => value] to be saved
	 *
	 * @return array  Validation errors: [optionname => error message]
	 *
	 * Return array must be empty if no error.
	 */
	public function saveOptions(array $data): array;
}
