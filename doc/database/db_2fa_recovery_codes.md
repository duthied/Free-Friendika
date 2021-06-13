Table 2fa_recovery_codes
===========
Two-factor authentication recovery codes

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uid | User ID | mediumint unsigned | YES | PRI |  |  |    
| code | Recovery code string | varchar(50) | YES | PRI |  |  |    
| generated | Datetime the code was generated | datetime | YES |  |  |  |    
| used | Datetime the code was used | datetime | NO |  |  |  |    

Return to [database documentation](help/database)
