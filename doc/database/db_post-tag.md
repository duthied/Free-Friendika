Table post-tag
===========

post relation to tags

Fields
------

| Field  | Description                                               | Type             | Null | Key | Default | Extra |
| ------ | --------------------------------------------------------- | ---------------- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned     | NO   | PRI | NULL    |       |
| type   |                                                           | tinyint unsigned | NO   | PRI | 0       |       |
| tid    |                                                           | int unsigned     | NO   | PRI | 0       |       |
| cid    | Contact id of the mentioned public contact                | int unsigned     | NO   | PRI | 0       |       |

Indexes
------------

| Name    | Fields                 |
| ------- | ---------------------- |
| PRIMARY | uri-id, type, tid, cid |
| tid     | tid                    |
| cid     | cid                    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| tid | [tag](help/database/db_tag) | id |
| cid | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
