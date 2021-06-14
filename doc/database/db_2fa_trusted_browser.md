Table 2fa_trusted_browser
===========
Two-factor authentication trusted browsers

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| cookie_hash | Trusted cookie hash | varchar(80) | YES | PRI | NULL |  |    
| uid | User ID | mediumint unsigned | YES |  | NULL |  |    
| user_agent | User agent string | text | NO |  | NULL |  |    
| created | Datetime the trusted browser was recorded | datetime | YES |  | NULL |  |    
| last_used | Datetime the trusted browser was last used | datetime | NO |  | NULL |  |    

Return to [database documentation](help/database)
