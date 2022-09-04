Table register
===========

registrations requiring admin approval

Fields
------

| Field    | Description   | Type               | Null | Key | Default             | Extra          |
| -------- | ------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id       | sequential ID | int unsigned       | NO   | PRI | NULL                | auto_increment |
| hash     |               | varbinary(255)     | NO   |     |                     |                |
| created  |               | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| uid      | User id       | mediumint unsigned | NO   |     | 0                   |                |
| password |               | varchar(255)       | NO   |     |                     |                |
| language |               | varchar(16)        | NO   |     |                     |                |
| note     |               | text               | YES  |     | NULL                |                |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
