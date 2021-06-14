Table item-uri
===========

URI and GUID for items

| Field | Description                     | Type           | Null | Key | Default | Extra          |
| ----- | ------------------------------- | -------------- | ---- | --- | ------- | -------------- |
| id    |                                 | int unsigned   | NO   | PRI | NULL    | auto_increment |
| uri   | URI of an item                  | varbinary(255) | NO   |     | NULL    |                |
| guid  | A unique identifier for an item | varbinary(255) | YES  |     | NULL    |                |

Return to [database documentation](help/database)
