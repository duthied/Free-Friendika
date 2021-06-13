Table delayed-post
===========
Posts that are about to be distributed at a later time

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id |  | int unsigned | YES | PRI |  | auto_increment |    
| uri | URI of the post that will be distributed later | varchar(255) | NO |  |  |  |    
| uid | Owner User id | mediumint unsigned | NO |  |  |  |    
| delayed | delay time | datetime | NO |  |  |  |    

Return to [database documentation](help/database)
