Table 2fa_recovery_codes
===========
Two-factor authentication recovery codes

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uid | User ID | mediumint unsigned | NO | PRI | NULL |  |    
| code | Recovery code string | varchar(50) | NO | PRI | NULL |  |    
| generated | Datetime the code was generated | datetime | NO |  | NULL |  |    
| used | Datetime the code was used | datetime | YES |  | NULL |  |    

Return to [database documentation](help/database)
