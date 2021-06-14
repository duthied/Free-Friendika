Table addon
===========

registered addons

Fields
------

| Field        | Description                                   | Type         | Null | Key | Default | Extra          |
| ------------ | --------------------------------------------- | ------------ | ---- | --- | ------- | -------------- |
| id           |                                               | int unsigned | NO   | PRI | NULL    | auto_increment |
| name         | addon base (file)name                         | varchar(50)  | NO   |     |         |                |
| version      | currently unused                              | varchar(50)  | NO   |     |         |                |
| installed    | currently always 1                            | boolean      | NO   |     | 0       |                |
| hidden       | currently unused                              | boolean      | NO   |     | 0       |                |
| timestamp    | file timestamp to check for reloads           | int unsigned | NO   |     | 0       |                |
| plugin_admin | 1 = has admin config, 0 = has no admin config | boolean      | NO   |     | 0       |                |

Indexes
------------

| Name           | Fields          |
| -------------- | --------------- |
| PRIMARY        | id              |
| installed_name | installed, name |
| name           | UNIQUE, name    |


Return to [database documentation](help/database)
