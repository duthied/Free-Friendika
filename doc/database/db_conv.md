Table conv
===========
private messages

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| guid | A unique identifier for this conversation | varchar(255) | YES |  |  |  |    
| recips | sender_handle;recipient_handle | text | NO |  |  |  |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| creator | handle of creator | varchar(255) | YES |  |  |  |    
| created | creation timestamp | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| updated | edited timestamp | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| subject | subject of initial message | text | NO |  |  |  |    

Return to [database documentation](help/database)
