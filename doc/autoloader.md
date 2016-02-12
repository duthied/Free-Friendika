Autoloader
==========

* [Home](help)

There is some initial support to class autoloading in Friendica core.

The autoloader code is in `include/autoloader.php`.
It's derived from composer autoloader code.

Namespaces and Classes are mapped to folders and files in `library/`,
and the map must be updated by hand, because we don't use composer yet.
The mapping is defined by files in `include/autoloader/` folder.

Currently, only HTMLPurifier library is loaded using autoloader.


