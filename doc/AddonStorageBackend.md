Friendica Storage Backend Addon development
===========================================

* [Home](help)

Storage backends can be added via addons.
A storage backend is implemented as a class, and the plugin register the class to make it avaiable to the system.

## The Storage Backend Class

The class must live in `Friendica\Addon\youraddonname` namespace, where `youraddonname` the folder name of your addon.

The class must implement `Friendica\Model\Storage\IStorage` interface. All method in the interface must be implemented:

namespace Friendica\Model\Storage;

```php
interface IStorage
{
	public static function get($ref);
	public static function put($data, $ref = "");
	public static function delete($ref);
	public static function getOptions();
	public static function saveOptions($data);
}
```

- `get($ref)` returns data pointed by `$ref`
- `put($data, $ref)` saves data in `$data` to position `$ref`, or a new position if `$ref` is empty.
- `delete($ref)` delete data pointed by `$ref`

Each storage backend can have options the admin can set in admin page.

- `getOptions()` returns an array with details about each option to build the interface.
- `saveOptions($data)` get `$data` from admin page, validate it and save it.

The array returned by `getOptions()` is defined as:

	[
		'option1name' => [ ..info.. ],
		'option2name' => [ ..info.. ],
		...
	]

An empty array can be returned if backend doesn't have any options.

The info array for each option is defined as:

	[
		'type',

define the field used in form, and the type of data.
one of 'checkbox', 'combobox', 'custom', 'datetime', 'input', 'intcheckbox', 'password', 'radio', 'richtext', 'select', 'select_raw', 'textarea', 'yesno'

		'label',

Translatable label of the field. This label will be shown in admin page

		value,

Current value of the option

		'help text',

Translatable description for the field. Will be shown in admin page

		extra data

Optional. Depends on which 'type' this option is:

- 'select': array `[ value => label ]` of choices
- 'intcheckbox': value of input element
- 'select_raw': prebuild html string of `<option >` tags
- 'yesno': array `[ 'label no', 'label yes']`

Each label should be translatable

	];


See doxygen documentation of `IStorage` interface for details about each method.

## Register a storage backend class

Each backend must be registered in the system when the plugin is installed, to be aviable.

`Friendica\Core\StorageManager::register($name, $class)` is used to register the backend class.
The `$name` must be univocal and will be shown to admin.

When the plugin is uninstalled, registered backends must be unregistered using
`Friendica\Core\StorageManager::unregister($class)`.

## Example

Here an hypotetical addon which register an unusefull storage backend.
Let's call it `samplestorage`.

This backend will discard all data we try to save and will return always the same image when we ask for some data.
The image returned can be set by the administrator in admin page.

First, the backend class.
The file will be `addon/samplestorage/SampleStorageBackend.php`:

```php
<?php
namespace Friendica\Addon\samplestorage;

use Friendica\Model\Storage\IStorage;

use Friendica\Core\Config;
use Friendica\Core\L10n;

class SampleStorageBackend implements IStorage
{
	public static function get($ref)
	{
		// we return alwais the same image data. Which file we load is defined by
		// a config key
		$filename = Config::get("storage", "samplestorage", "sample.jpg");
		return file_get_contents($filename);
	}
	
	public static function put($data, $ref = "")
	{
		if ($ref === "") {
			$ref = "sample";
		}
		// we don't save $data !
		return $ref;
	}
	
	public static function delete($ref)
	{
		// we pretend to delete the data
		return true;
	}
	
	public static function getOptions()
	{
		$filename = Config::get("storage", "samplestorage", "sample.jpg");
		return [
			"filename" => [
				"input",	// will use a simple text input
				L10n::t("The file to return"),	// the label
				$filename,	// the current value
				L10n::t("Enter the path to a file"), // the help text
				// no extra data for "input" type..
		];
	}
	
	public static function saveOptions($data)
	{
		// the keys in $data are the same keys we defined in getOptions()
		$newfilename = trim($data["filename"]);
		
		// this function should always validate the data.
		// in this example we check if file exists
		if (!file_exists($newfilename)) {
			// in case of error we return an array with
			// ["optionname" => "error message"]
			return ["filename" => "The file doesn't exists"];
		}
		
		Config::set("storage", "samplestorage", $newfilename);
		
		// no errors, return empty array
		return [];
	}
}
```

Now the plugin main file. Here we register and unregister the backend class.

The file is `addon/samplestorage/samplestorage.php`

```php
<?php
/**
 * Name: Sample Storage Addon
 * Description: A sample addon which implements an unusefull storage backend
 * Version: 1.0.0
 * Author: Alice <https://alice.social/~alice>
 */

use Friendica\Addon\samplestorage\SampleStorageBackend;
use Friendica\DI;

function samplestorage_install()
{
	// on addon install, we register our class with name "Sample Storage".
	// note: we use `::class` property, which returns full class name as string
	// this save us the problem of correctly escape backslashes in class name
	DI::facStorage()->register("Sample Storage", SampleStorageBackend::class);
}

function samplestorage_unistall()
{
	// when the plugin is uninstalled, we unregister the backend.
	DI::facStorage()->unregister("Sample Storage");
}
```



