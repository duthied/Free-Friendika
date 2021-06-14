Table 2fa_recovery_codes
===========
Two-factor authentication recovery codes

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uid | User ID | mediumint unsigned | YES | PRI | NULL |  |    
| code | Recovery code string | varchar(50) | YES | PRI | NULL |  |    
| generated | Datetime the code was generated | datetime | YES |  | NULL |  |    
| used | Datetime the code was used | datetime | NO |  | NULL |  |    

Return to [database documentation](help/database)
