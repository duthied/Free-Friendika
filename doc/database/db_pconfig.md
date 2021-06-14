Table pconfig
===========
personal (per user) configuration storage

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id  | Primary key | int unsigned       | NO  | PRI | NULL | auto_increment |    
| uid | User id     | mediumint unsigned | NO  |     | 0    |                |    
| cat | Category    | varchar(50)        | NO  |     |      |                |    
| k   | Key         | varchar(100)       | NO  |     |      |                |    
| v   | Value       | mediumtext         | YES |     | NULL |                |    

Return to [database documentation](help/database)
