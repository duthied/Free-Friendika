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

This type of modules mainly responds either with encoded `XML` or with `JSON` output.
It isn't intended to respond with human readable text.

A frontend module should extend the [`BaseModule`](https://github.com/friendica/friendica/blob/develop/src/BaseModule.php), especially the `rawContent()` method.
 
### Routing

Every module needs to be accessed within a route.
The routes are defined inside [`Router->collectRoutes()`](https://github.com/friendica/friendica/blob/develop/src/App/Router.php).

Use the given routes as a pattern for further routes.

The routing library and further documentation can be found [here](https://github.com/nikic/FastRoute).