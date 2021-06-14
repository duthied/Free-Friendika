Table notify-threads
===========



| Field                | Description                                   | Type               | Null | Key | Default | Extra          |
| -------------------- | --------------------------------------------- | ------------------ | ---- | --- | ------- | -------------- |
| id                   | sequential ID                                 | int unsigned       | NO   | PRI | NULL    | auto_increment |
| notify-id            |                                               | int unsigned       | NO   |     | 0       |                |
| master-parent-item   | Deprecated                                    | int unsigned       | YES  |     | NULL    |                |
| master-parent-uri-id | Item-uri id of the parent of the related post | int unsigned       | YES  |     | NULL    |                |
| parent-item          |                                               | int unsigned       | NO   |     | 0       |                |
| receiver-uid         | User id                                       | mediumint unsigned | NO   |     | 0       |                |

Return to [database documentation](help/database)
