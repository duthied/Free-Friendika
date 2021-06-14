Table application-token
===========
OAuth user token

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| application-id |  | int unsigned | YES | PRI | NULL |  |    
| uid | Owner User id | mediumint unsigned | YES | PRI | NULL |  |    
| code |  | varchar(64) | YES |  | NULL |  |    
| access_token |  | varchar(64) | YES |  | NULL |  |    
| created_at | creation time | datetime | YES |  | NULL |  |    
| scopes |  | varchar(255) | NO |  | NULL |  |    
| read | Read scope | boolean | NO |  | NULL |  |    
| write | Write scope | boolean | NO |  | NULL |  |    
| follow | Follow scope | boolean | NO |  | NULL |  |    
| push | Push scope | boolean | NO |  | NULL |  |    

Return to [database documentation](help/database)
