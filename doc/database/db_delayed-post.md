Table delayed-post
===========

Posts that are about to be distributed at a later time

Fields
------

| Field   | Description                                    | Type               | Null | Key | Default | Extra          |
| ------- | ---------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id      |                                                | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uri     | URI of the post that will be distributed later | varchar(255)       | YES  |     | NULL    |                |
| uid     | Owner User id                                  | mediumint unsigned | YES  |     | NULL    |                |
| delayed | delay time                                     | datetime           | YES  |     | NULL    |                |

Indexes
------------

| Name    | Fields                |
| ------- | --------------------- |
| PRIMARY | id                    |
| uid_uri | UNIQUE, uid, uri(190) |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
