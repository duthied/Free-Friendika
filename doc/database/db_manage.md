Table manage
===========

table of accounts that can manage each other

Fields
------

| Field | Description   | Type               | Null | Key | Default | Extra          |
| ----- | ------------- | ------------------ | ---- | --- | ------- | -------------- |
| id    | sequential ID | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid   | User id       | mediumint unsigned | NO   |     | 0       |                |
| mid   | User id       | mediumint unsigned | NO   |     | 0       |                |

Indexes
------------

| Name    | Fields           |
| ------- | ---------------- |
| PRIMARY | id               |
| uid_mid | UNIQUE, uid, mid |
| mid     | mid              |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| mid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
