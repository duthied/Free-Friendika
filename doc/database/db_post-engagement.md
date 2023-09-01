Table post-engagement
===========

Engagement data per post

Fields
------

| Field        | Description                                                     | Type               | Null | Key | Default | Extra |
| ------------ | --------------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uri-id       | Id of the item-uri table entry that contains the item uri       | int unsigned       | NO   | PRI | NULL    |       |
| author-id    | Link to the contact table with uid=0 of the author of this item | int unsigned       | NO   |     | 0       |       |
| contact-type | Person, organisation, news, community, relay                    | tinyint            | NO   |     | 0       |       |
| created      |                                                                 | datetime           | YES  |     | NULL    |       |
| comments     | Number of comments                                              | mediumint unsigned | YES  |     | NULL    |       |
| activities   | Number of activities (like, dislike, ...)                       | mediumint unsigned | YES  |     | NULL    |       |

Indexes
------------

| Name      | Fields    |
| --------- | --------- |
| PRIMARY   | uri-id    |
| author-id | author-id |
| created   | created   |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |
| author-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
