Table post-searchindex
===========

Content for all posts

Fields
------

| Field      | Description                                               | Type             | Null | Key | Default | Extra |
| ---------- | --------------------------------------------------------- | ---------------- | ---- | --- | ------- | ----- |
| uri-id     | Id of the item-uri table entry that contains the item uri | int unsigned     | NO   | PRI | NULL    |       |
| network    |                                                           | char(4)          | YES  |     | NULL    |       |
| private    | 0=public, 1=private, 2=unlisted                           | tinyint unsigned | YES  |     | NULL    |       |
| searchtext | Simplified text for the full text search                  | mediumtext       | YES  |     | NULL    |       |

Indexes
------------

| Name       | Fields               |
| ---------- | -------------------- |
| PRIMARY    | uri-id               |
| searchtext | FULLTEXT, searchtext |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
