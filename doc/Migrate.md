Migrating to a different Server
===============

* [Home](help)

## Preparation

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

This process may take a long time depending on the size of your storage. 

### Cleaning up

[Removing expired items]

### Configuration files


### Go offline 
Take your web server offline. 

## Dumping DB


## Transferring to new installation 

