Table notify
===========
notifications

| Field         | Description                                   | Type               | Null | Key | Default             | Extra          |    
| ------------- | --------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |    
| id            | sequential ID                                 | int unsigned       | NO   | PRI | NULL                | auto_increment |    
| type          |                                               | smallint unsigned  | NO   |     | 0                   |                |    
| name          |                                               | varchar(255)       | NO   |     |                     |                |    
| url           |                                               | varchar(255)       | NO   |     |                     |                |    
| photo         |                                               | varchar(255)       | NO   |     |                     |                |    
| date          |                                               | datetime           | NO   |     | 0001-01-01 00:00:00 |                |    
| msg           |                                               | mediumtext         | YES  |     | NULL                |                |    
| uid           | Owner User id                                 | mediumint unsigned | NO   |     | 0                   |                |    
| link          |                                               | varchar(255)       | NO   |     |                     |                |    
| iid           |                                               | int unsigned       | YES  |     | NULL                |                |    
| parent        |                                               | int unsigned       | YES  |     | NULL                |                |    
| uri-id        | Item-uri id of the related post               | int unsigned       | YES  |     | NULL                |                |    
| parent-uri-id | Item-uri id of the parent of the related post | int unsigned       | YES  |     | NULL                |                |    
| seen          |                                               | boolean            | NO   |     | 0                   |                |    
| verb          |                                               | varchar(100)       | NO   |     |                     |                |    
| otype         |                                               | varchar(10)        | NO   |     |                     |                |    
| name_cache    | Cached bbcode parsing of name                 | tinytext           | YES  |     | NULL                |                |    
| msg_cache     | Cached bbcode parsing of msg                  | mediumtext         | YES  |     | NULL                |                |    

Return to [database documentation](help/database)
