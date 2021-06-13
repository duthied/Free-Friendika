Table mail
===========
private messages

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| guid | A unique identifier for this private message | varchar(255) | YES |  |  |  |    
| from-name | name of the sender | varchar(255) | YES |  |  |  |    
| from-photo | contact photo link of the sender | varchar(255) | YES |  |  |  |    
| from-url | profile linke of the sender | varchar(255) | YES |  |  |  |    
| contact-id | contact.id | varchar(255) | NO |  |  |  |    
| author-id | Link to the contact table with uid=0 of the author of the mail | int unsigned | NO |  |  |  |    
| convid | conv.id | int unsigned | NO |  |  |  |    
| title |  | varchar(255) | YES |  |  |  |    
| body |  | mediumtext | NO |  |  |  |    
| seen | if message visited it is 1 | boolean | YES |  | 0 |  |    
| reply |  | boolean | YES |  | 0 |  |    
| replied |  | boolean | YES |  | 0 |  |    
| unknown | if sender not in the contact table this is 1 | boolean | YES |  | 0 |  |    
| uri |  | varchar(255) | YES |  |  |  |    
| uri-id | Item-uri id of the related mail | int unsigned | NO |  |  |  |    
| parent-uri |  | varchar(255) | YES |  |  |  |    
| parent-uri-id | Item-uri id of the parent of the related mail | int unsigned | NO |  |  |  |    
| thr-parent |  | varchar(255) | NO |  |  |  |    
| thr-parent-id | Id of the item-uri table that contains the thread parent uri | int unsigned | NO |  |  |  |    
| created | creation time of the private message | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
