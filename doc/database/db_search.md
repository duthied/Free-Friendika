Table search
===========



Fields
------

| Field | Description   | Type               | Null | Key | Default | Extra          |
| ----- | ------------- | ------------------ | ---- | --- | ------- | -------------- |
| id    | sequential ID | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid   | User id       | mediumint unsigned | NO   |     | 0       |                |
| term  |               | varchar(255)       | NO   |     |         |                |

Indexes
------------

| Name     | Fields        |
| -------- | ------------- |
| PRIMARY  | id            |
| uid_term | uid, term(64) |
| term     | term(64)      |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
