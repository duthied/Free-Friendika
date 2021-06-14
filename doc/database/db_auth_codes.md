Table auth_codes
===========
OAuth usage

| Field        | Description | Type         | Null | Key | Default | Extra |    
| ------------ | ----------- | ------------ | ---- | --- | ------- | ----- |    
| id           |             | varchar(40)  | NO   | PRI | NULL    |       |    
| client_id    |             | varchar(20)  | NO   |     |         |       |    
| redirect_uri |             | varchar(200) | NO   |     |         |       |    
| expires      |             | int          | NO   |     | 0       |       |    
| scope        |             | varchar(250) | NO   |     |         |       |    

Return to [database documentation](help/database)
