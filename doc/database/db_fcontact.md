Table fcontact
===========

Diaspora compatible contacts - used in the Diaspora implementation

Fields
------

| Field             | Description                                                   | Type             | Null | Key | Default             | Extra          |
| ----------------- | ------------------------------------------------------------- | ---------------- | ---- | --- | ------------------- | -------------- |
| id                | sequential ID                                                 | int unsigned     | NO   | PRI | NULL                | auto_increment |
| guid              | unique id                                                     | varbinary(255)   | NO   |     |                     |                |
| url               |                                                               | varbinary(383)   | NO   |     |                     |                |
| uri-id            | Id of the item-uri table entry that contains the fcontact url | int unsigned     | YES  |     | NULL                |                |
| name              |                                                               | varchar(255)     | NO   |     |                     |                |
| photo             |                                                               | varbinary(383)   | NO   |     |                     |                |
| request           |                                                               | varbinary(383)   | NO   |     |                     |                |
| nick              |                                                               | varchar(255)     | NO   |     |                     |                |
| addr              |                                                               | varchar(255)     | NO   |     |                     |                |
| batch             |                                                               | varbinary(383)   | NO   |     |                     |                |
| notify            |                                                               | varbinary(383)   | NO   |     |                     |                |
| poll              |                                                               | varbinary(383)   | NO   |     |                     |                |
| confirm           |                                                               | varbinary(383)   | NO   |     |                     |                |
| priority          |                                                               | tinyint unsigned | NO   |     | 0                   |                |
| network           |                                                               | char(4)          | NO   |     |                     |                |
| alias             |                                                               | varbinary(383)   | NO   |     |                     |                |
| pubkey            |                                                               | text             | YES  |     | NULL                |                |
| created           |                                                               | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| updated           |                                                               | datetime         | NO   |     | 0001-01-01 00:00:00 |                |
| interacting_count | Number of contacts this contact interactes with               | int unsigned     | YES  |     | 0                   |                |
| interacted_count  | Number of contacts that interacted with this contact          | int unsigned     | YES  |     | 0                   |                |
| post_count        | Number of posts and comments                                  | int unsigned     | YES  |     | 0                   |                |

Indexes
------------

| Name    | Fields           |
| ------- | ---------------- |
| PRIMARY | id               |
| addr    | addr(32)         |
| url     | UNIQUE, url(190) |
| uri-id  | UNIQUE, uri-id   |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
