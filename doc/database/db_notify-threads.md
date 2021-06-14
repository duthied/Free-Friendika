Table notify-threads
===========


| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| notify-id |  | int unsigned | YES |  | 0 |  |    
| master-parent-item | Deprecated | int unsigned | NO |  | NULL |  |    
| master-parent-uri-id | Item-uri id of the parent of the related post | int unsigned | NO |  | NULL |  |    
| parent-item |  | int unsigned | YES |  | 0 |  |    
| receiver-uid | User id | mediumint unsigned | YES |  | 0 |  |    

Return to [database documentation](help/database)
