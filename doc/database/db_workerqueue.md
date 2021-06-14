Table workerqueue
===========
Background tasks queue entries

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id        | Auto incremented worker task id                         | int unsigned     | NO  | PRI | NULL                | auto_increment |    
| command   | Task command                                            | varchar(100)     | YES |     | NULL                |                |    
| parameter | Task parameter                                          | mediumtext       | YES |     | NULL                |                |    
| priority  | Task priority                                           | tinyint unsigned | NO  |     | 0                   |                |    
| created   | Creation date                                           | datetime         | NO  |     | 0001-01-01 00:00:00 |                |    
| pid       | Process id of the worker                                | int unsigned     | NO  |     | 0                   |                |    
| executed  | Execution date                                          | datetime         | NO  |     | 0001-01-01 00:00:00 |                |    
| next_try  | Next retrial date                                       | datetime         | NO  |     | 0001-01-01 00:00:00 |                |    
| retrial   | Retrial counter                                         | tinyint          | NO  |     | 0                   |                |    
| done      | Marked 1 when the task was done - will be deleted later | boolean          | NO  |     | 0                   |                |    

Return to [database documentation](help/database)
