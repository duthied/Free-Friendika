## Friendica\Worker

The Worker namespace contains all asynchronous workers of Friendica.
The all have to implement the function `public static function execute()`.

They are all executed by the [`Worker`](https://github.com/friendica/friendica/blob/develop/src/Core/Worker.php).