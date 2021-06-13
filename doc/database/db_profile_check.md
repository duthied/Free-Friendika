Table profile_check
===========
DFRN remote auth use

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| cid | contact.id | int unsigned | YES |  | 0 |  |    
| dfrn_id |  | varchar(255) | YES |  |  |  |    
| sec |  | varchar(255) | YES |  |  |  |    
| expire |  | int unsigned | YES |  | 0 |  |    

Return to [database documentation](help/database)
