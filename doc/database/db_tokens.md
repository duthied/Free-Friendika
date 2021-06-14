Table tokens
===========
OAuth usage

| Field     | Description | Type               | Null | Key | Default | Extra |    
| --------- | ----------- | ------------------ | ---- | --- | ------- | ----- |    
| id        |             | varchar(40)        | NO   | PRI | NULL    |       |    
| secret    |             | text               | YES  |     | NULL    |       |    
| client_id |             | varchar(20)        | NO   |     |         |       |    
| expires   |             | int                | NO   |     | 0       |       |    
| scope     |             | varchar(200)       | NO   |     |         |       |    
| uid       | User id     | mediumint unsigned | NO   |     | 0       |       |    

Return to [database documentation](help/database)
