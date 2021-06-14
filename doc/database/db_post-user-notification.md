Table post-user-notification
===========

User post notifications

| Field             | Description                                               | Type               | Null | Key | Default | Extra |
| ----------------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uri-id            | Id of the item-uri table entry that contains the item uri | int unsigned       | NO   | PRI | NULL    |       |
| uid               | Owner id which owns this copy of the item                 | mediumint unsigned | NO   | PRI | NULL    |       |
| notification-type |                                                           | tinyint unsigned   | NO   |     | 0       |       |

Return to [database documentation](help/database)
