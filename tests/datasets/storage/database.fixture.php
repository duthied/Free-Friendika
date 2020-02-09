<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

return [
	'photo'   => [
		// move from data-attribute to storage backend
		[
			'id'            => 1,
			'backend-class' => null,
			'backend-ref'   => 'f0c0d0i2',
			'data'          => 'without class',
		],
		// move from storage-backend to maybe filesystem backend, skip at database backend
		[
			'id'            => 2,
			'backend-class' => 'Database',
			'backend-ref'   => 1,
			'data'          => '',
		],
		// move data if invalid storage
		[
			'id'            => 3,
			'backend-class' => 'invalid!',
			'backend-ref'   => 'unimported',
			'data'          => 'invalid data moved',
		],
		// skip everytime because of invalid storage and no data
		[
			'id'            => 3,
			'backend-class' => 'invalid!',
			'backend-ref'   => 'unimported',
			'data'          => '',
		],
	],
	'storage' => [
		[
			'id'   => 1,
			'data' => 'inside database',
		],
	],
];
