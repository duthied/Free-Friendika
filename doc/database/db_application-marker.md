Table application-marker
===========

Timeline marker

Fields
------

| Field          | Description                  | Type               | Null | Key | Default | Extra |
| -------------- | ---------------------------- | ------------------ | ---- | --- | ------- | ----- |
| application-id |                              | int unsigned       | NO   | PRI | NULL    |       |
| uid            | Owner User id                | mediumint unsigned | NO   | PRI | NULL    |       |
| timeline       | Marker (home, notifications) | varchar(64)        | NO   | PRI | NULL    |       |
| last_read_id   | Marker id for the timeline   | varbinary(383)     | YES  |     | NULL    |       |
| version        | Version number               | smallint unsigned  | YES  |     | NULL    |       |
| updated_at     | creation time                | datetime           | YES  |     | NULL    |       |

Indexes
------------

| Name    | Fields                        |
| ------- | ----------------------------- |
| PRIMARY | application-id, uid, timeline |
| uid_id  | uid                           |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| application-id | [application](help/database/db_application) | id |
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
