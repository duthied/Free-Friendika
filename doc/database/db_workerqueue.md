Table workerqueue
===========
Background tasks queue entries

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | Auto incremented worker task id | int unsigned | YES | PRI |  | auto_increment |    
| command | Task command | varchar(100) | NO |  |  |  |    
| parameter | Task parameter | mediumtext | NO |  |  |  |    
| priority | Task priority | tinyint unsigned | YES |  | 0 |  |    
| created | Creation date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| pid | Process id of the worker | int unsigned | YES |  | 0 |  |    
| executed | Execution date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| next_try | Next retrial date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| retrial | Retrial counter | tinyint | YES |  | 0 |  |    
| done | Marked 1 when the task was done - will be deleted later | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
