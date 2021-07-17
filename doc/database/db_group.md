Table group
===========

privacy groups, group info

Fields
------

| Field   | Description                                | Type               | Null | Key | Default | Extra          |
| ------- | ------------------------------------------ | ------------------ | ---- | --- | ------- | -------------- |
| id      | sequential ID                              | int unsigned       | NO   | PRI | NULL    | auto_increment |
| uid     | Owner User id                              | mediumint unsigned | NO   |     | 0       |                |
| visible | 1 indicates the member list is not private | boolean            | NO   |     | 0       |                |
| deleted | 1 indicates the group has been deleted     | boolean            | NO   |     | 0       |                |
| name    | human readable name of group               | varchar(255)       | NO   |     |         |                |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | id     |
| uid     | uid    |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
