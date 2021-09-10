Table user-contact
===========

User specific public contact data

Fields
------

| Field                     | Description                                                  | Type               | Null | Key | Default | Extra |
| ------------------------- | ------------------------------------------------------------ | ------------------ | ---- | --- | ------- | ----- |
| cid                       | Contact id of the linked public contact                      | int unsigned       | NO   | PRI | 0       |       |
| uid                       | User id                                                      | mediumint unsigned | NO   | PRI | 0       |       |
| uri-id                    | Id of the item-uri table entry that contains the contact url | int unsigned       | YES  |     | NULL    |       |
| blocked                   | Contact is completely blocked for this user                  | boolean            | YES  |     | NULL    |       |
| ignored                   | Posts from this contact are ignored                          | boolean            | YES  |     | NULL    |       |
| collapsed                 | Posts from this contact are collapsed                        | boolean            | YES  |     | NULL    |       |
| rel                       | The kind of the relation between the user and the contact    | tinyint unsigned   | YES  |     | NULL    |       |
| info                      |                                                              | mediumtext         | YES  |     | NULL    |       |
| notify_new_posts          |                                                              | boolean            | YES  |     | NULL    |       |
| fetch_further_information |                                                              | tinyint unsigned   | YES  |     | NULL    |       |
| ffi_keyword_denylist      |                                                              | text               | YES  |     | NULL    |       |

Indexes
------------

| Name       | Fields              |
| ---------- | ------------------- |
| PRIMARY    | uid, cid            |
| cid        | cid                 |
| uri-id_uid | UNIQUE, uri-id, uid |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| cid | [contact](help/database/db_contact) | id |
| uid | [user](help/database/db_user) | uid |
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
