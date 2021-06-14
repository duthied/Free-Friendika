Table notify
===========
notifications

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| type |  | smallint unsigned | YES |  | 0 |  |    
| name |  | varchar(255) | YES |  |  |  |    
| url |  | varchar(255) | YES |  |  |  |    
| photo |  | varchar(255) | YES |  |  |  |    
| date |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| msg |  | mediumtext | NO |  | NULL |  |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| link |  | varchar(255) | YES |  |  |  |    
| iid |  | int unsigned | NO |  | NULL |  |    
| parent |  | int unsigned | NO |  | NULL |  |    
| uri-id | Item-uri id of the related post | int unsigned | NO |  | NULL |  |    
| parent-uri-id | Item-uri id of the parent of the related post | int unsigned | NO |  | NULL |  |    
| seen |  | boolean | YES |  | 0 |  |    
| verb |  | varchar(100) | YES |  |  |  |    
| otype |  | varchar(10) | YES |  |  |  |    
| name_cache | Cached bbcode parsing of name | tinytext | NO |  | NULL |  |    
| msg_cache | Cached bbcode parsing of msg | mediumtext | NO |  | NULL |  |    

Return to [database documentation](help/database)
