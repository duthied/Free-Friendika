Table register
===========
registrations requiring admin approval

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| hash |  | varchar(255) | YES |  |  |  |    
| created |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| password |  | varchar(255) | YES |  |  |  |    
| language |  | varchar(16) | YES |  |  |  |    
| note |  | text | NO |  | NULL |  |    

Return to [database documentation](help/database)
