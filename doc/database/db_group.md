Table group
===========
privacy groups, group info

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| visible | 1 indicates the member list is not private | boolean | YES |  | 0 |  |    
| deleted | 1 indicates the group has been deleted | boolean | YES |  | 0 |  |    
| name | human readable name of group | varchar(255) | YES |  |  |  |    

Return to [database documentation](help/database)
