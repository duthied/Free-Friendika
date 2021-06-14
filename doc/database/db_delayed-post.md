Table delayed-post
===========
Posts that are about to be distributed at a later time

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id      |                                                | int unsigned       | NO  | PRI | NULL | auto_increment |    
| uri     | URI of the post that will be distributed later | varchar(255)       | YES |     | NULL |                |    
| uid     | Owner User id                                  | mediumint unsigned | YES |     | NULL |                |    
| delayed | delay time                                     | datetime           | YES |     | NULL |                |    

Return to [database documentation](help/database)
