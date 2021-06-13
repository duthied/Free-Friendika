Table application
===========
OAuth application

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | generated index | int unsigned | YES | PRI |  | auto_increment |    
| client_id |  | varchar(64) | YES |  |  |  |    
| client_secret |  | varchar(64) | YES |  |  |  |    
| name |  | varchar(255) | YES |  |  |  |    
| redirect_uri |  | varchar(255) | YES |  |  |  |    
| website |  | varchar(255) | NO |  |  |  |    
| scopes |  | varchar(255) | NO |  |  |  |    
| read | Read scope | boolean | NO |  |  |  |    
| write | Write scope | boolean | NO |  |  |  |    
| follow | Follow scope | boolean | NO |  |  |  |    
| push | Push scope | boolean | NO |  |  |  |    

Return to [database documentation](help/database)
