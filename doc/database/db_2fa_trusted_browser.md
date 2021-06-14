Table 2fa_trusted_browser
===========
Two-factor authentication trusted browsers

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| cookie_hash | Trusted cookie hash                        | varchar(80)        | NO  | PRI | NULL |  |    
| uid         | User ID                                    | mediumint unsigned | NO  |     | NULL |  |    
| user_agent  | User agent string                          | text               | YES |     | NULL |  |    
| created     | Datetime the trusted browser was recorded  | datetime           | NO  |     | NULL |  |    
| last_used   | Datetime the trusted browser was last used | datetime           | YES |     | NULL |  |    

Return to [database documentation](help/database)
