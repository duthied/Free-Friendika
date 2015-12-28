Table notify-threads
====================

| Field              | Description      | Type             | Null | Key | Default | Extra          |
|--------------------|------------------|------------------|------|-----|---------|----------------|
| id                 | sequential ID    | int(11)          | NO   | PRI | NULL    | auto_increment |
| notify-id          |                  | int(11)          | NO   |     | 0       |                |
| master-parent-item |                  | int(10) unsigned | NO   | MUL | 0       |                |
| parent-item        |                  | int(10) unsigned | NO   |     | 0       |                |
| receiver-uid       |                  | int(11)          | NO   | MUL | 0       |                |

Return to [database documentation](help/database)
