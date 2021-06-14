Table locks
===========


| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| name |  | varchar(128) | YES |  |  |  |    
| locked |  | boolean | YES |  | 0 |  |    
| pid | Process ID | int unsigned | YES |  | 0 |  |    
| expires | datetime of cache expiration | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
