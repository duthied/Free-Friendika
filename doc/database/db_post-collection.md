Table post-collection
===========

Collection of posts

Fields
------

| Field  | Description                                               | Type             | Null | Key | Default | Extra |
| ------ | --------------------------------------------------------- | ---------------- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned     | NO   | PRI | NULL    |       |
| type   | 0 - Featured                                              | tinyint unsigned | NO   | PRI | 0       |       |

Indexes
------------

| Name    | Fields       |
| ------- | ------------ |
| PRIMARY | uri-id, type |
| type    | type         |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
