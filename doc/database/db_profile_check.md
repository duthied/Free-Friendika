Table profile_check
===================

| Field    | Description   | Type             | Null | Key | Default | Extra           |
| -------- | ------------- | ---------------- | ---- | --- | ------- | --------------- |
| id       | sequential ID | int(10) unsigned | NO   | PRI | NULL    | auto_increment  |
| uid      | user.id       | int(10) unsigned | NO   |     | 0       |                 |
| cid      | contact.id    | int(10) unsigned | NO   |     | 0       |                 |
| dfrn_id  |               | varchar(255)     | NO   |     |         |                 |
| sec      |               | varchar(255)     | NO   |     | 0       |                 |
| expire   |               | int(11)          | NO   |     | NULL    |                 |

Return to [database documentation](help/database)
