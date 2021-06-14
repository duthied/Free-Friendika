Table fsuggest
===========

friend suggestion stuff

| Field   | Description | Type               | Null | Key | Default             | Extra          |
| ------- | ----------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id      |             | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid     | User id     | mediumint unsigned | NO   |     | 0                   |                |
| cid     |             | int unsigned       | NO   |     | 0                   |                |
| name    |             | varchar(255)       | NO   |     |                     |                |
| url     |             | varchar(255)       | NO   |     |                     |                |
| request |             | varchar(255)       | NO   |     |                     |                |
| photo   |             | varchar(255)       | NO   |     |                     |                |
| note    |             | text               | YES  |     | NULL                |                |
| created |             | datetime           | NO   |     | 0001-01-01 00:00:00 |                |

Return to [database documentation](help/database)
