Table application-token
===========
OAuth user token

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| application-id |  | int unsigned | YES | PRI |  |  |    
| uid | Owner User id | mediumint unsigned | YES | PRI |  |  |    
| code |  | varchar(64) | YES |  |  |  |    
| access_token |  | varchar(64) | YES |  |  |  |    
| created_at | creation time | datetime | YES |  |  |  |    
| scopes |  | varchar(255) | NO |  |  |  |    
| read | Read scope | boolean | NO |  |  |  |    
| write | Write scope | boolean | NO |  |  |  |    
| follow | Follow scope | boolean | NO |  |  |  |    
| push | Push scope | boolean | NO |  |  |  |    

Return to [database documentation](help/database)
