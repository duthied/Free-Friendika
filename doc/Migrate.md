Migrating to a new server
===============

* [Home](help)

## Preparation

### New server
Set up your new server as described [here](install) by following installation procedure until you have created a database.

### Head up to users

Inform your users of an upcoming interruption to your service. To ensure no loss of data, your server needs to be offline during some part of the migration processes.

You may find these addons useful for in communicating with your users prior to the migration process:

* blackout
* notifyall


### Storage
Check your storage backend with ``bin/console storage list`` in the root folder.

If you are not currently using ``Database`` run the following commands:
1. ``bin/console storage set Database`` to active the database backend.
2. ``bin/console storage move`` to initiate moving the stored image files.

This process may take a long time depending on the size of your storage and your server capacity. Prior to initiating this process, you may want to check the number of files in the storage with the following command: ``tree -if -I index.html /path/to/storage/``.

### Cleaning up

Before transferring your database, you may want to clean it up by ensuring the expiration of items is set to reasonable value in the administrator panel. *Admin* > *Site* > *Performance* > Enable "Clean up database" 

After adjusting these settings, the database cleaning up processes will be initiated according to your configured daily cron time frame.

To review the size of your database, log into MySQL with ``mysql -p`` run the following query: 
``SELECT table_schema AS "Database", SUM(data_length + index_length) / 1024 / 1024 / 1024 AS "Size (GB)" FROM information_schema.TABLES GROUP BY table_schema;``

You should see an output like this:
````
+--------------------+----------------+
| Database           | Size (GB)      |
+--------------------+----------------+
| friendica_db       | 8.054092407227 |
| [..........]       | [...........]  |
+--------------------+----------------+
````

Finally, you may also want optimise your database with the following command:
``mysqloptimize -p friendica-db``

### Go offline 
Take your web server offline. This will ensure consistency of your users' data.

## Dumping DB

Dump you database: ``mysqldump  -p friendica_db > friendica_db-$(date +%Y%m%d).sql``

and possibly compress it. 

## Transferring to new installation 

Transfer your database and copy of your configuration file ``config/local.config.php-copy`` to your new server.

## Import your DB

Import your database: ``mysql -p friendica_db < your-friendica_db-file.sql``

## Completing installation process

Complete the installation by adjusting the configuration settings and set up the required daily cron job.



