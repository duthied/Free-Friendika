Table application-token
===========

OAuth user token

| Field          | Description   | Type               | Null | Key | Default | Extra |
| -------------- | ------------- | ------------------ | ---- | --- | ------- | ----- |
| application-id |               | int unsigned       | NO   | PRI | NULL    |       |
| uid            | Owner User id | mediumint unsigned | NO   | PRI | NULL    |       |
| code           |               | varchar(64)        | NO   |     | NULL    |       |
| access_token   |               | varchar(64)        | NO   |     | NULL    |       |
| created_at     | creation time | datetime           | NO   |     | NULL    |       |
| scopes         |               | varchar(255)       | YES  |     | NULL    |       |
| read           | Read scope    | boolean            | YES  |     | NULL    |       |
| write          | Write scope   | boolean            | YES  |     | NULL    |       |
| follow         | Follow scope  | boolean            | YES  |     | NULL    |       |
| push           | Push scope    | boolean            | YES  |     | NULL    |       |

Return to [database documentation](help/database)
