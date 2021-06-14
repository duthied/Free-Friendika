Table delayed-post
===========
Posts that are about to be distributed at a later time

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id |  | int unsigned | YES | PRI | NULL | auto_increment |    
| uri | URI of the post that will be distributed later | varchar(255) | NO |  | NULL |  |    
| uid | Owner User id | mediumint unsigned | NO |  | NULL |  |    
| delayed | delay time | datetime | NO |  | NULL |  |    

Return to [database documentation](help/database)
