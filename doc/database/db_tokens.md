Table tokens
===========

OAuth usage

Fields
------

| Field     | Description | Type               | Null | Key | Default | Extra |
| --------- | ----------- | ------------------ | ---- | --- | ------- | ----- |
| id        |             | varchar(40)        | NO   | PRI | NULL    |       |
| secret    |             | text               | YES  |     | NULL    |       |
| client_id |             | varchar(20)        | NO   |     |         |       |
| expires   |             | int                | NO   |     | 0       |       |
| scope     |             | varchar(200)       | NO   |     |         |       |
| uid       | User id     | mediumint unsigned | NO   |     | 0       |       |

Indexes
------------

| Name | Fields |
|------|---------|
| PRIMARY | id |
| client_id | client_id |
| uid | uid |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| client_id | [clients](help/database/db_clients) | client_id |
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
