Table 2fa_app_specific_password
===========
Two-factor app-specific _password

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | Password ID for revocation | mediumint unsigned | YES | PRI | NULL | auto_increment |    
| uid | User ID | mediumint unsigned | YES |  | NULL |  |    
| description | Description of the usage of the password | varchar(255) | NO |  | NULL |  |    
| hashed_password | Hashed password | varchar(255) | YES |  | NULL |  |    
| generated | Datetime the password was generated | datetime | YES |  | NULL |  |    
| last_used | Datetime the password was last used | datetime | NO |  | NULL |  |    

Return to [database documentation](help/database)
