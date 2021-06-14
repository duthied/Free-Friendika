Table tag
===========

tags and mentions

Fields
------

| Field | Description | Type           | Null | Key | Default | Extra          |
| ----- | ----------- | -------------- | ---- | --- | ------- | -------------- |
| id    |             | int unsigned   | NO   | PRI | NULL    | auto_increment |
| name  |             | varchar(96)    | NO   |     |         |                |
| url   |             | varbinary(255) | NO   |     |         |                |

Indexes
------------

| Name          | Fields            |
| ------------- | ----------------- |
| PRIMARY       | id                |
| type_name_url | UNIQUE, name, url |
| url           | url               |


Return to [database documentation](help/database)
