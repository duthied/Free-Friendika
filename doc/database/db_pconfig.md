Table pconfig
===========
personal (per user) configuration storage

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | Primary key | int unsigned | YES | PRI |  | auto_increment |    
| uid | User id | mediumint unsigned | YES |  | 0 |  |    
| cat | Category | varchar(50) | YES |  |  |  |    
| k | Key | varchar(100) | YES |  |  |  |    
| v | Value | mediumtext | NO |  |  |  |    

Return to [database documentation](help/database)
