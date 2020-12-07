Table tokens
============

| Field      | Description | Type         | Null | Key | Default | Extra |
| ---------- | ----------- | ------------ | ---- | --- | ------- | ----- |
| id         |             | varchar(40)  | NO   | PRI | NULL    |       |
| secret     |             | text         | NO   |     | NULL    |       |
| client_id  |             | varchar(20)  | NO   |     |         |       |
| expires    |             | int(11)      | NO   |     | 0       |       |
| scope      |             | varchar(200) | NO   |     |         |       |
| uid        |             | int(11)      | NO   |     | 0       |       |

Return to [database documentation](help/database)
