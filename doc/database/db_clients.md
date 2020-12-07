Table clients
=============

| Field         | Description | Type         | Null | Key | Default | Extra |
| ------------- | ----------- | ------------ | ---- | --- | ------- | ----- |
| client_id     |             | varchar(20)  | NO   | PRI | NULL    |       |
| pw            |             | varchar(20)  | NO   |     |         |       |
| redirect_uri  |             | varchar(200) | NO   |     |         |       |
| name          |             | text         | YES  |     | NULL    |       |
| icon          |             | text         | YES  |     | NULL    |       |
| uid           |             | int(11)      | NO   |     | 0       |       |

Return to [database documentation](help/database)
