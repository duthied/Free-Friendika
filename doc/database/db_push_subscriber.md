Table push_subscriber
===========
Used for OStatus: Contains feed subscribers

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| callback_url |  | varchar(255) | YES |  |  |  |    
| topic |  | varchar(255) | YES |  |  |  |    
| nickname |  | varchar(255) | YES |  |  |  |    
| push | Retrial counter | tinyint | YES |  | 0 |  |    
| last_update | Date of last successful trial | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| next_try | Next retrial date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| renewed | Date of last subscription renewal | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| secret |  | varchar(255) | YES |  |  |  |    

Return to [database documentation](help/database)
