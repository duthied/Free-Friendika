Table user-contact
===========

User specific public contact data

| Field     | Description                                 | Type               | Null | Key | Default | Extra |
| --------- | ------------------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| cid       | Contact id of the linked public contact     | int unsigned       | NO   | PRI | 0       |       |
| uid       | User id                                     | mediumint unsigned | NO   | PRI | 0       |       |
| blocked   | Contact is completely blocked for this user | boolean            | YES  |     | NULL    |       |
| ignored   | Posts from this contact are ignored         | boolean            | YES  |     | NULL    |       |
| collapsed | Posts from this contact are collapsed       | boolean            | YES  |     | NULL    |       |

Return to [database documentation](help/database)
