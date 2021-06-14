Table fcontact
===========
Diaspora compatible contacts - used in the Diaspora implementation

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| guid | unique id | varchar(255) | YES |  |  |  |    
| url |  | varchar(255) | YES |  |  |  |    
| name |  | varchar(255) | YES |  |  |  |    
| photo |  | varchar(255) | YES |  |  |  |    
| request |  | varchar(255) | YES |  |  |  |    
| nick |  | varchar(255) | YES |  |  |  |    
| addr |  | varchar(255) | YES |  |  |  |    
| batch |  | varchar(255) | YES |  |  |  |    
| notify |  | varchar(255) | YES |  |  |  |    
| poll |  | varchar(255) | YES |  |  |  |    
| confirm |  | varchar(255) | YES |  |  |  |    
| priority |  | tinyint unsigned | YES |  | 0 |  |    
| network |  | char(4) | YES |  |  |  |    
| alias |  | varchar(255) | YES |  |  |  |    
| pubkey |  | text | NO |  | NULL |  |    
| updated |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
