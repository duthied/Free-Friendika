Migrating to a new server installation
===============

* [Home](help)

## Preparation

### New server
Set up your new server as described [here](Install); follow the installation procedure until you have created a database.

### Heads up to users
Inform your users of an upcoming interruption to your service. To ensure data consistency, your server needs to be offline during some steps of the migration processes.

You may also find these addons useful for communicating with your users prior to the migration process:

* blackout
* notifyall


### Storage
Check your storage backend with ``bin/console storage list`` in the root folder. The output should look like this:
````
Sel | Name                
-----------------------
     | Filesystem          
 *   | Database  
````

If you are *not* using ``Database`` run the following commands:

1.  ``bin/console storage set Database`` to activate the database backend.
2.  ``bin/console storage move`` to initiate moving the stored image files.

This process may take a long time depending on the size of your storage and your server's capacity. Prior to initiating this process, you may want to check the number of files in the storage with the following command: ``tree -if -I index.html /path/to/storage/``.

### Cleaning up
Before transferring your database, you may want to clean it up; ensure the expiration of database items is set to a reasonable value and activated via the administrator panel. *Admin* > *Site* > *Performance* > Enable "Clean up database" 

After adjusting these settings, the database cleaning up processes will be initiated according to your configured daily cron job.

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

Finally, you may also want to optimise your database with the following command:
``mysqloptimize -p friendica-db``

### Going offline 
Stop background tasks and put your server in maintenance mode.

1.  If you had set up a worker cron job like this ``*/10 * * * * cd /home/myname/mywebsite; /usr/bin/php bin/worker.php`` run ``crontab -e`` and comment out this line.  Alternatively if you deploy a worker daemon, disable this instead.
2.  Put your server into maintenance mode with a command like this: ``bin/console maintenance 1 "We are currently upgrading our system and will be back soon."``

## Dumping DB
Dump you database: ``mysqldump  -p friendica_db > friendica_db-$(date +%Y%m%d).sql``
and possibly compress it. 

## Transferring to new server
Transfer your database and a copy of your configuration file ``config/local.config.php-copy`` to your new server installation.

## Restoring your DB
Import your database on your new server: ``mysql -p friendica_db < your-friendica_db-file.sql``

## Completing installation process
Ensure your DNS settings point to your new server.

Complete the installation by adjusting the configuration settings and set up the required daily cron job.





