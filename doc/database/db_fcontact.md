Table fcontact
===========

Diaspora compatible contacts - used in the Diaspora implementation

Fields
------

| Field             | Description                                                   | Type             | Null | Key | Default             | Extra          |
| ----------------- | ------------------------------------------------------------- | ---------------- | ---- | --- | ------------------- | -------------- |
| id                | sequential ID                                                 | int unsigned     | NO   | PRI | NULL                | auto_increment |
| guid              | unique id                                                     | varchar(255)     | NO   |     |                     |                |
| url               |                                                               | varchar(255)     | NO   |     |                     |                |
| uri-id            | Id of the item-uri table entry that contains the fcontact url | int unsigned     | YES  |     | NULL                |                |
| name              |                                                               | varchar(255)     | NO   |     |                     |                |
| photo             |                                                               | varchar(255)     | NO   |     |                     |                |
| request           |                                                               | varchar(255)     | NO   |     |                     |                |
| nick              |                                                               | varchar(255)     | NO   |     |                     |                |
| addr              |                                                               | varchar(255)     | NO   |     |                     |                |
| batch             |                                                               | varchar(255)     | NO   |     |                     |                |
| notify            |                                                               | varchar(255)     | NO   |     |                     |                |
| poll              |                                                               | varchar(255)     | NO   |     |                     |                |
| confirm           |                                                               | varchar(255)     | NO   |     |                     |                |
| priority          |                                                               | tinyint unsigned | NO   |     | 0                   |                |
| network           |                                                               | char(4)          | NO   |     |                     |                |
| alias             |                                                               | varchar(255)     | NO   |     |                     |                |
| pubkey            |                                                               | text             | YES  |     | NULL                |                |
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
