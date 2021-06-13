Table 2fa_app_specific_password
===========
Two-factor app-specific _password

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | Password ID for revocation | mediumint unsigned | YES | PRI |  | auto_increment |    
| uid | User ID | mediumint unsigned | YES |  |  |  |    
| description | Description of the usage of the password | varchar(255) | NO |  |  |  |    
| hashed_password | Hashed password | varchar(255) | YES |  |  |  |    
| generated | Datetime the password was generated | datetime | YES |  |  |  |    
| last_used | Datetime the password was last used | datetime | NO |  |  |  |    

Return to [database documentation](help/database)
