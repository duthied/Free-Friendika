Table host
===========

Hostname

Fields
------

| Field | Description   | Type             | Null | Key | Default | Extra          |
| ----- | ------------- | ---------------- | ---- | --- | ------- | -------------- |
| id    | sequential ID | tinyint unsigned | NO   | PRI | NULL    | auto_increment |
| name  | The hostname  | varchar(128)     | NO   |     |         |                |

Indexes
------------

| Name    | Fields       |
| ------- | ------------ |
| PRIMARY | id           |
| name    | UNIQUE, name |


Return to [database documentation](help/database)
