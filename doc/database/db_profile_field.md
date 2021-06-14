Table profile_field
===========
Custom profile fields

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner user id | mediumint unsigned | YES |  | 0 |  |    
| order | Field ordering per user | mediumint unsigned | YES |  | 1 |  |    
| psid | ID of the permission set of this profile field - 0 = public | int unsigned | NO |  | NULL |  |    
| label | Label of the field | varchar(255) | YES |  |  |  |    
| value | Value of the field | text | NO |  | NULL |  |    
| created | creation time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| edited | last edit time | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
