Table group_member
===========

privacy groups, member info

| Field      | Description                                               | Type         | Null | Key | Default | Extra          |
| ---------- | --------------------------------------------------------- | ------------ | ---- | --- | ------- | -------------- |
| id         | sequential ID                                             | int unsigned | NO   | PRI | NULL    | auto_increment |
| gid        | groups.id of the associated group                         | int unsigned | NO   |     | 0       |                |
| contact-id | contact.id of the member assigned to the associated group | int unsigned | NO   |     | 0       |                |

Return to [database documentation](help/database)
