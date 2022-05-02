Table post-delivery
===========

Status of ActivityPub inboxes

Fields
------

| Field    | Description                                               | Type               | Null | Key | Default             | Extra |
| -------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | ----- |
| uri-id   | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL                |       |
| inbox-id | Item-uri id of inbox url                                  | int unsigned       | NO   | PRI | NULL                |       |
| uid      | Delivering user                                           | mediumint unsigned | YES  |     | NULL                |       |
| created  |                                                           | datetime           | YES  |     | 0001-01-01 00:00:00 |       |
| command  |                                                           | varbinary(32)      | YES  |     | NULL                |       |

Indexes
------------

| Name             | Fields            |
| ---------------- | ----------------- |
| PRIMARY          | uri-id, inbox-id  |
| inbox-id_created | inbox-id, created |
| uid              | uid               |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| inbox-id | [item-uri](help/database/db_item-uri) | id |
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
