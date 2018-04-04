Table group
===========

| Field   | Description                                | Type             | Null | Key | Default | Extra           |
| ------- | ------------------------------------------ | ---------------- | ---- | --- | ------- | --------------- |
| id      | sequential ID                              | int(10) unsigned | NO   | PRI | NULL    | auto_increment  |
| uid     | user.id owning this data                   | int(10) unsigned | NO   | MUL | 0       |                 |
| visible | 1 indicates the member list is not private | tinyint(1)       | NO   |     | 0       |                 |
| deleted | 1 indicates the group has been deleted     | tinyint(1)       | NO   |     | 0       |                 |
| name    | human readable name of group               | varchar(255)     | NO   |     |         |                 |

Return to [database documentation](help/database)
