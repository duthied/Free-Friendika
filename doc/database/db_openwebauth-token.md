Table openwebauth-token
===========
Store OpenWebAuth token to verify contacts

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | User id - currently unused | mediumint unsigned | YES |  | 0 |  |    
| type | Verify type | varchar(32) | YES |  |  |  |    
| token | A generated token | varchar(255) | YES |  |  |  |    
| meta |  | varchar(255) | YES |  |  |  |    
| created | datetime of creation | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
