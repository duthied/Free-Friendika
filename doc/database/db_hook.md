Table hook
==========

| Field    | Description                                                                                                | Type             | Null | Key | Default | Extra           |
| -------- | ---------------------------------------------------------------------------------------------------------- | ---------------- | ---- | --- | ------- | --------------- |
| id       | sequential ID                                                                                              | int(11)          | NO   | PRI | NULL    | auto_increment  |
| hook     | name of hook                                                                                               | varchar(255)     | NO   | MUL |         |                 |
| file     | relative filename of hook handler                                                                          | varchar(255)     | NO   |     |         |                 |
| function | function name of hook handler                                                                              | varchar(255)     | NO   |     |         |                 |
| priority | not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order | int(11) unsigned | NO   |     | 0       |                 |

Return to [database documentation](help/database)
