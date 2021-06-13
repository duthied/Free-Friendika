Table mailacct
===========
Mail account data for fetching mails

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| server |  | varchar(255) | YES |  |  |  |    
| port |  | smallint unsigned | YES |  | 0 |  |    
| ssltype |  | varchar(16) | YES |  |  |  |    
| mailbox |  | varchar(255) | YES |  |  |  |    
| user |  | varchar(255) | YES |  |  |  |    
| pass |  | text | NO |  |  |  |    
| reply_to |  | varchar(255) | YES |  |  |  |    
| action |  | tinyint unsigned | YES |  | 0 |  |    
| movetofolder |  | varchar(255) | YES |  |  |  |    
| pubmail |  | boolean | YES |  | 0 |  |    
| last_check |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
