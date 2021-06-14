Table intro
===========


| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| fid |  | int unsigned | NO |  | NULL |  |    
| contact-id |  | int unsigned | YES |  | 0 |  |    
| knowyou |  | boolean | YES |  | 0 |  |    
| duplex |  | boolean | YES |  | 0 |  |    
| note |  | text | NO |  | NULL |  |    
| hash |  | varchar(255) | YES |  |  |  |    
| datetime |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| blocked |  | boolean | YES |  | 1 |  |    
| ignore |  | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
