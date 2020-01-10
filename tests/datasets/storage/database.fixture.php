<?php

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
