Table application
===========
OAuth application

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | generated index | int unsigned | YES | PRI | NULL | auto_increment |    
| client_id |  | varchar(64) | YES |  | NULL |  |    
| client_secret |  | varchar(64) | YES |  | NULL |  |    
| name |  | varchar(255) | YES |  | NULL |  |    
| redirect_uri |  | varchar(255) | YES |  | NULL |  |    
| website |  | varchar(255) | NO |  | NULL |  |    
| scopes |  | varchar(255) | NO |  | NULL |  |    
| read | Read scope | boolean | NO |  | NULL |  |    
| write | Write scope | boolean | NO |  | NULL |  |    
| follow | Follow scope | boolean | NO |  | NULL |  |    
| push | Push scope | boolean | NO |  | NULL |  |    

Return to [database documentation](help/database)
