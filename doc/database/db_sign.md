Table sign
==========

| Field        | Description   | Type             | Null | Key | Default | Extra           |
| ------------ | ------------- | ---------------- | ---- | --- | ------- | --------------- |
| id           | sequential ID | int(10) unsigned | NO   | PRI | NULL    | auto_increment  |
| iid          | item.id       | int(10) unsigned | NO   | MUL | 0       |                 |
| signed_text  |               | mediumtext       | NO   |     | NULL    |                 |
| signature    |               | text             | NO   |     | NULL    |                 |
| signer       |               | varchar(255)     | NO   |     |         |                 |

Return to [database documentation](help/database)
