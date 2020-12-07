Table session
=============

| Field  | Description   | Type                | Null | Key | Default | Extra           |
| ------ | ------------- | ------------------- | ---- | --- | ------- | --------------- |
| id     | sequential ID | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment  |
| sid    |               | varchar(255)        | NO   | MUL |         |                 |
| data   |               | text                | NO   |     | NULL    |                 |
| expire |               | int(10) unsigned    | NO   | MUL | 0       |                 |

Return to [database documentation](help/database)
