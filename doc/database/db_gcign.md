Table gcign
===========

| Field | Description                    | Type    | Null | Key | Default | Extra           |
| ----- | ------------------------------ | ------- | ---- | --- | ------- | --------------- |
| id    | sequential ID                  | int(11) | NO   | PRI | NULL    | auto_increment  |
| uid   | local user.id                  | int(11) | NO   | MUL | 0       |                 |
| gcid  | gcontact.id of ignored contact | int(11) | NO   | MUL | 0       |                 |

Return to [database documentation](help/database)
