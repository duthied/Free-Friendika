Table unique_contacts
=====================

| Field    | Description      | Type         | Null | Key | Default | Extra          |
|----------|------------------|--------------|------|-----|---------|----------------|
| id       | sequential ID    | int(11)      | NO   | PRI | NULL    | auto_increment |
| url      |                  | varchar(255) | NO   | MUL |         |                |
| nick     |                  | varchar(255) | NO   |     |         |                |
| name     |                  | varchar(255) | NO   |     |         |                |
| avatar   |                  | varchar(255) | NO   |     |         |                |
| location |                  | varchar(255) | NO   |     |         |                |
| about    |                  | text         | NO   |     | NULL    |                |

Return to [database documentation](help/database)
