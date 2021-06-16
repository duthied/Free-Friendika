Table intro
===========



Fields
------

| Field      | Description   | Type               | Null | Key | Default             | Extra          |
| ---------- | ------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id         | sequential ID | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid        | User id       | mediumint unsigned | NO   |     | 0                   |                |
| fid        |               | int unsigned       | YES  |     | NULL                |                |
| contact-id |               | int unsigned       | NO   |     | 0                   |                |
| knowyou    |               | boolean            | NO   |     | 0                   |                |
| duplex     |               | boolean            | NO   |     | 0                   |                |
| note       |               | text               | YES  |     | NULL                |                |
| hash       |               | varchar(255)       | NO   |     |                     |                |
| datetime   |               | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| blocked    |               | boolean            | NO   |     | 1                   |                |
| ignore     |               | boolean            | NO   |     | 0                   |                |

Indexes
------------

| Name       | Fields     |
| ---------- | ---------- |
| PRIMARY    | id         |
| contact-id | contact-id |
| uid        | uid        |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| contact-id | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
