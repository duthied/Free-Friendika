## Friendica\Module

The Module namespace contains the different modules of Friendica.
Each module is loaded through the [`App`](https://github.com/friendica/friendica/blob/develop/src/App.php).

There are mainly two types of modules:
-	frontend modules to interact with users
-	backend modules to interact with machine requests

### Frontend modules

This type of modules mainly needs a template, which are generally located at
[view/templates/](https://github.com/friendica/friendica/tree/develop/view/templates).

A frontend module should extend the [`BaseModule`](https://github.com/friendica/friendica/blob/develop/src/BaseModule.php), especially the `content()` method. 

### Backend modules

This type of modules mainly responds either with `XML` or with `JSON`. 

Rules for Modules:
- 	Named like the call (i.e. https://friendica.test/contact => `Contact`)
-	Start with capitals and are **not** camelCased.
-	Directly interacting with a given request (POST or GET)
-	Extending [`BaseModule`](https://github.com/friendica/friendica/blob/develop/src/BaseModule.php).