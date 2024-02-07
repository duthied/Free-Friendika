Table user-gserver
===========

User settings about remote servers

Fields
------

| Field   | Description                              | Type               | Null | Key | Default | Extra |
| ------- | ---------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uid     | Owner User id                            | mediumint unsigned | NO   | PRI | 0       |       |
| gsid    | Gserver id                               | int unsigned       | NO   | PRI | 0       |       |
| ignored | server accounts are ignored for the user | boolean            | NO   |     | 0       |       |

Indexes
------------

| Name    | Fields    |
| ------- | --------- |
| PRIMARY | uid, gsid |
| gsid    | gsid      |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
