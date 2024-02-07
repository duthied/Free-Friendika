Table gserver-tag
===========

Tags that the server has subscribed

Fields
------

| Field      | Description                        | Type         | Null | Key | Default | Extra |
| ---------- | ---------------------------------- | ------------ | ---- | --- | ------- | ----- |
| gserver-id | The id of the gserver              | int unsigned | NO   | PRI | 0       |       |
| tag        | Tag that the server has subscribed | varchar(100) | NO   | PRI |         |       |

Indexes
------------

| Name    | Fields          |
| ------- | --------------- |
| PRIMARY | gserver-id, tag |
| tag     | tag             |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| gserver-id | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
