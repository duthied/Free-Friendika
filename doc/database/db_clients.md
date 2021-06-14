Table clients
===========

OAuth usage

Fields
------

| Field        | Description | Type               | Null | Key | Default | Extra |
| ------------ | ----------- | ------------------ | ---- | --- | ------- | ----- |
| client_id    |             | varchar(20)        | NO   | PRI | NULL    |       |
| pw           |             | varchar(20)        | NO   |     |         |       |
| redirect_uri |             | varchar(200)       | NO   |     |         |       |
| name         |             | text               | YES  |     | NULL    |       |
| icon         |             | text               | YES  |     | NULL    |       |
| uid          | User id     | mediumint unsigned | NO   |     | 0       |       |

Indexes
------------

| Name | Fields |
|------|--------|
| PRIMARY | client_id |
| uid | uid |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
