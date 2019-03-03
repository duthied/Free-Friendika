## Friendica\Module

The Module namespace contains the different modules of Friendica.
Each module is loaded through the [`App`](https://github.com/friendica/friendica/blob/develop/src/App.php).

Rules for Modules:
- 	Named like the call (i.e. https://friendica.test/contact => `Contact`)
-	Start with capitals and are **not** camelCased.
-	Directly interacting with a given request (POST or GET)
-	Extending [`BaseModule`](https://github.com/friendica/friendica/blob/develop/src/BaseModule.php).