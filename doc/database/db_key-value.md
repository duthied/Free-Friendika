Table key-value
===========

A key value storage

Fields
------

| Field | Description | Type          | Null | Key | Default | Extra          |
| ----- | ----------- | ------------- | ---- | --- | ------- | -------------- |
| id    |             | int unsigned  | NO   | PRI | NULL    | auto_increment |
| k     |             | varbinary(50) | NO   |     |         |                |
| v     |             | mediumtext    | YES  |     | NULL    |                |

Indexes
------------

| Name    | Fields    |
| ------- | --------- |
| PRIMARY | id        |
| k       | UNIQUE, k |


Return to [database documentation](help/database)
