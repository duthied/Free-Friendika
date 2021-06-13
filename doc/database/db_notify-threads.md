Table notify-threads
===========


| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| notify-id |  | int unsigned | YES |  | 0 |  |    
| master-parent-item | Deprecated | int unsigned | NO |  |  |  |    
| master-parent-uri-id | Item-uri id of the parent of the related post | int unsigned | NO |  |  |  |    
| parent-item |  | int unsigned | YES |  | 0 |  |    
| receiver-uid | User id | mediumint unsigned | YES |  | 0 |  |    

Return to [database documentation](help/database)
