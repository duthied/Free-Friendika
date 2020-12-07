Table group_member
==================

| Field      | Description                                                 | Type             | Null | Key | Default | Extra           |
| ---------- | ----------------------------------------------------------- | ---------------- | ---- | --- | ------- | --------------- |
| id         | sequential ID                                               | int(10) unsigned | NO   | PRI | NULL    | auto_increment  |
| uid        | user.id of the owner of this data                           | int(10) unsigned | NO   | MUL | 0       |                 |
| gid        | groups.id of the associated group                           | int(10) unsigned | NO   |     | 0       |                 |
| contact-id | contact.id  of the member assigned to the associated group  | int(10) unsigned | NO   |     | 0       |                 |

Return to [database documentation](help/database)
